<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListMovementsForAdminDataInput;
use App\Fixtures\Training\EquipmentFixtures;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMovementUseCase;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use App\UseCase\Training\Admin\ListMovementsForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Listing the common movements for the back-office, filtered by status, family, muscle, group and equipment. */
final class ListMovementsForAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MovementTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    /** How many movements the fixtures seed. */
    private const int SEEDED_MOVEMENTS = 68;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testItListsEveryCommonMovementByName(): void
    {
        $movements = $this->list(new ListMovementsForAdminDataInput());

        self::assertCount(self::SEEDED_MOVEMENTS, $movements);
        self::assertSame('Ab wheel rollout', $movements[0]->name);
    }

    public function testAnOutputCarriesWhatTheMovementTargetsAndUses(): void
    {
        $bench = $this->only(new ListMovementsForAdminDataInput(movementFamilyId: $this->family(MovementFamilyFixtures::BENCH_PRESS)->id), 'Bench press (barbell)');

        self::assertSame('Bench press', $bench->movementFamilyName);
        self::assertSame('Mid chest', $bench->primaryMuscle->name);
        self::assertSame(['Front delts', 'Triceps'], array_map(static fn ($muscle) => $muscle->name, $bench->secondaryMuscles));
        self::assertSame(['Barbell', 'Bench'], array_map(static fn ($equipment) => $equipment->name, $bench->equipments));
        self::assertTrue($bench->tracksReps);
        self::assertTrue($bench->tracksWeight);
        self::assertFalse($bench->tracksDuration);
        self::assertFalse($bench->tracksDistance);
    }

    public function testTheListFiltersByFamily(): void
    {
        self::assertSame(['Plank', 'Side plank'], $this->names(new ListMovementsForAdminDataInput(
            movementFamilyId: $this->family(MovementFamilyFixtures::PLANK)->id,
        )));
    }

    /** A muscle matches as primary or as secondary — and the secondary muscles come back whole. */
    public function testTheMuscleFilterMatchesPrimaryAndSecondary(): void
    {
        $triceps = $this->muscleNamed('Triceps');

        $names = $this->names(new ListMovementsForAdminDataInput(muscleId: $triceps->id));

        self::assertContains('Triceps pushdown (cable)', $names);
        self::assertContains('Bench press (barbell)', $names);
        self::assertNotContains('Plank', $names);

        $bench = $this->only(new ListMovementsForAdminDataInput(muscleId: $triceps->id), 'Bench press (barbell)');
        self::assertCount(2, $bench->secondaryMuscles);
    }

    public function testTheGroupFilterMatchesPrimaryAndSecondary(): void
    {
        $arms = $this->getReference(MuscleGroupFixtures::ARMS, MuscleGroupDataModel::class);

        $names = $this->names(new ListMovementsForAdminDataInput(muscleGroupId: $arms->id));

        self::assertContains('Curl (barbell)', $names);
        // Forearms, a secondary muscle of the deadlift, sit in Arms.
        self::assertContains('Deadlift (barbell)', $names);
        self::assertNotContains('Leg extension (machine)', $names);
    }

    /** The equipment filter hands back the movement with every equipment it has, not only the one asked for. */
    public function testTheEquipmentFilterKeepsTheOtherEquipments(): void
    {
        $bench = $this->getReference(EquipmentFixtures::BENCH, EquipmentDataModel::class);

        $movement = $this->only(new ListMovementsForAdminDataInput(equipmentId: $bench->id), 'Bench press (barbell)');

        self::assertSame(['Barbell', 'Bench'], array_map(static fn ($equipment) => $equipment->name, $movement->equipments));
    }

    public function testTheFiltersCombine(): void
    {
        self::getContainer()->get(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movement(MovementFixtures::BENCH_PRESS_BARBELL)->id ?? 0);

        self::assertSame(['Bench press (dumbbell)'], $this->names(new ListMovementsForAdminDataInput(
            isActive: true,
            movementFamilyId: $this->family(MovementFamilyFixtures::BENCH_PRESS)->id,
        )));
    }

    public function testListingTheMovementsStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ListMovementsForAdminUseCase::class)->execute($currentUser, new ListMovementsForAdminDataInput()),
        );
    }

    /** Grown with movements carrying secondary muscles and equipments: a collection read per movement would show. */
    public function testListingTheMovementsDoesNotGrowWithThem(): void
    {
        $inputs = [
            $this->create('Floor press (dumbbell)'),
            $this->create('Floor press (kettlebell)', equipmentIds: [$this->equipmentNamed('Kettlebell')->id ?? 0, $this->equipmentNamed('Weighted vest')->id ?? 0]),
        ];

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ListMovementsForAdminUseCase::class)->execute($currentUser, new ListMovementsForAdminDataInput()),
            function () use ($inputs): void {
                foreach ($inputs as $input) {
                    self::getContainer()->get(CreateMovementUseCase::class)->execute($this->currentAdmin(), $input);
                }
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }

    /** @return list<\App\Domain\DTO\Output\Training\MovementDataOutput> */
    private function list(ListMovementsForAdminDataInput $input): array
    {
        return self::getContainer()->get(ListMovementsForAdminUseCase::class)->execute($this->currentAdmin(), $input);
    }

    /** @return list<string> */
    private function names(ListMovementsForAdminDataInput $input): array
    {
        return array_map(static fn ($movement) => $movement->name, $this->list($input));
    }

    private function only(ListMovementsForAdminDataInput $input, string $name): \App\Domain\DTO\Output\Training\MovementDataOutput
    {
        foreach ($this->list($input) as $movement) {
            if ($name === $movement->name) {
                return $movement;
            }
        }

        self::fail(sprintf('"%s" is not in the list.', $name));
    }
}
