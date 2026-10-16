<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Constraint\Training\MovementEquipmentsConstraint;
use App\Domain\Validation\Constraint\Training\MovementFamilyUsableConstraint;
use App\Domain\Validation\Constraint\Training\MovementMeasureConstraint;
use App\Domain\Validation\Constraint\Training\MovementMusclesConstraint;
use App\Domain\Validation\Constraint\Training\MovementNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMovementValidator;
use App\Fixtures\Training\EquipmentFixtures;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMovementUseCase;
use App\UseCase\Training\Admin\DeactivateEquipmentUseCase;
use App\UseCase\Training\Admin\DeactivateMovementFamilyUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Creating a common movement: its family, the muscles it targets, the equipments it uses and what it records. */
final class CreateMovementUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MovementTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MovementProviderGateway $movementProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movementProviderGateway = self::getContainer()->get(MovementProviderGateway::class);

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testItCreatesAMovement(): void
    {
        $output = self::getContainer()->get(CreateMovementUseCase::class)->execute($this->currentAdmin(), $this->create('Floor press (dumbbell)'));

        self::assertSame('Floor press (dumbbell)', $output->name);
        self::assertSame('Bench press', $output->movementFamilyName);
        self::assertSame('Mid chest', $output->primaryMuscle->name);
        self::assertSame(['Triceps'], array_map(static fn ($muscle) => $muscle->name, $output->secondaryMuscles));
        self::assertSame(['Dumbbell'], array_map(static fn ($equipment) => $equipment->name, $output->equipments));
        self::assertSame('https://example.com/floor-press', $output->videoUrl);
        self::assertTrue($output->isActive);

        $movement = $this->movementProviderGateway->findOneCommonById($output->id);
        self::assertNotNull($movement);
        self::assertNull($movement->owner);
        self::assertCount(1, $movement->secondaryMuscles);
    }

    /** No equipment at all is a bodyweight movement, and it is allowed. */
    public function testABodyweightMovementHasNoEquipment(): void
    {
        $output = self::getContainer()->get(CreateMovementUseCase::class)->execute($this->currentAdmin(), $this->create('Diamond push-up', equipmentIds: []));

        self::assertSame([], $output->equipments);
    }

    public function testItRefusesANameAlreadyTakenIgnoringCase(): void
    {
        $this->assertCreateRefused($this->create('bench press (BARBELL)'), 'name', MovementNameAvailableConstraint::NAME_ALREADY_USED);
    }

    public function testItRefusesAnInactiveFamily(): void
    {
        $family = $this->family(MovementFamilyFixtures::BENCH_PRESS);
        self::getContainer()->get(DeactivateMovementFamilyUseCase::class)->execute($this->currentAdmin(), $family->id ?? 0);

        $this->assertCreateRefused($this->create('Floor press (dumbbell)'), 'movementFamilyId', MovementFamilyUsableConstraint::MOVEMENT_FAMILY_INACTIVE);
    }

    public function testItRefusesAPrimaryMuscleInAnInactiveGroup(): void
    {
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)
            ->execute($this->currentAdmin(), $this->getReference(MuscleGroupFixtures::CHEST, MuscleGroupDataModel::class)->id ?? 0);

        $this->assertCreateRefused($this->create('Floor press (dumbbell)'), 'primaryMuscleId', MovementMusclesConstraint::PRIMARY_MUSCLE_UNAVAILABLE);
    }

    public function testItRefusesThePrimaryMuscleAmongTheSecondaryOnes(): void
    {
        $midChest = $this->muscleNamed('Mid chest');

        $this->assertCreateRefused(
            $this->create('Floor press (dumbbell)', secondaryMuscleIds: [$midChest->id ?? 0]),
            'secondaryMuscleIds',
            MovementMusclesConstraint::PRIMARY_MUSCLE_ALSO_SECONDARY,
        );
    }

    public function testItRefusesAnUnknownSecondaryMuscle(): void
    {
        $this->assertCreateRefused(
            $this->create('Floor press (dumbbell)', secondaryMuscleIds: [123456789]),
            'secondaryMuscleIds',
            MovementMusclesConstraint::SECONDARY_MUSCLE_NOT_FOUND,
        );
    }

    public function testItRefusesAnInactiveEquipment(): void
    {
        $dumbbell = $this->getReference(EquipmentFixtures::DUMBBELL, EquipmentDataModel::class);
        self::getContainer()->get(DeactivateEquipmentUseCase::class)->execute($this->currentAdmin(), $dumbbell->id ?? 0);

        $this->assertCreateRefused($this->create('Floor press (dumbbell)'), 'equipmentIds', MovementEquipmentsConstraint::EQUIPMENT_INACTIVE);
    }

    /** A weight alone says nothing about what was done with it. */
    public function testItRefusesAMovementThatRecordsNothingButAWeight(): void
    {
        $this->assertCreateRefused(
            $this->create('Floor press (dumbbell)', tracksReps: false),
            'measure',
            MovementMeasureConstraint::MEASURE_REQUIRED,
        );
    }

    public function testCreatingAMovementStaysWithinItsQueryBudget(): void
    {
        $input = $this->create('Floor press (dumbbell)');

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(CreateMovementUseCase::class)->execute($currentUser, $input),
            // Five reads, one per kind of row the input names, then the movement and one row per link: one secondary muscle, one equipment.
            max: 8,
        );
    }

    private function assertCreateRefused(CreateMovementDataInput $input, string $field, string $code): void
    {
        try {
            self::getContainer()->get(CreateMovementUseCase::class)->execute($this->currentAdmin(), $input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey($field, $exception->violations);
            self::assertContains($code, $exception->violations[$field]);
        }
    }
}
