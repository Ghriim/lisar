<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Admin\ListMovementsForAdminDataInput;
use App\Domain\DTO\Input\Workout\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Workout\CreateMovementDataInput;
use App\Domain\DTO\Input\Workout\CreateMovementFamilyDataInput;
use App\Domain\DTO\Input\Workout\StartWorkoutDataInput;
use App\Domain\DTO\Input\Workout\UpdateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\MovementProviderGateway;
use App\Domain\Validation\Constraint\Workout\EquipmentUnusedConstraint;
use App\Domain\Validation\Constraint\Workout\MovementEquipmentsConstraint;
use App\Domain\Validation\Constraint\Workout\MovementFamilyNameAvailableConstraint;
use App\Domain\Validation\Constraint\Workout\MovementFamilyUnusedConstraint;
use App\Domain\Validation\Constraint\Workout\MovementFamilyUsableConstraint;
use App\Domain\Validation\Constraint\Workout\MovementMeasureConstraint;
use App\Domain\Validation\Constraint\Workout\MovementMusclesConstraint;
use App\Domain\Validation\Constraint\Workout\MovementNameAvailableConstraint;
use App\Domain\Validation\Constraint\Workout\MovementUnusedConstraint;
use App\Domain\Validation\Constraint\Workout\MuscleUnusedConstraint;
use App\Domain\Validation\Validator\Workout\CreateMovementValidator;
use App\Domain\Validation\Validator\Workout\UpdateMovementValidator;
use App\Fixtures\EquipmentFixtures;
use App\Fixtures\MovementFamilyFixtures;
use App\Fixtures\MovementFixtures;
use App\Fixtures\MuscleGroupFixtures;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreateMovementFamilyUseCase;
use App\UseCase\Admin\CreateMovementUseCase;
use App\UseCase\Admin\DeactivateEquipmentUseCase;
use App\UseCase\Admin\DeactivateMovementFamilyUseCase;
use App\UseCase\Admin\DeactivateMovementUseCase;
use App\UseCase\Admin\DeactivateMuscleGroupUseCase;
use App\UseCase\Admin\DeleteEquipmentUseCase;
use App\UseCase\Admin\DeleteMovementFamilyUseCase;
use App\UseCase\Admin\DeleteMovementUseCase;
use App\UseCase\Admin\DeleteMuscleUseCase;
use App\UseCase\Admin\ListMovementFamiliesForAdminUseCase;
use App\UseCase\Admin\ListMovementsForAdminUseCase;
use App\UseCase\Admin\UpdateMovementUseCase;
use App\UseCase\Workout\AddWorkoutBlockUseCase;
use App\UseCase\Workout\StartWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The back-office maintaining the common movements and their families — one screen, one test. */
final class MovementBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    /** How many movements and families the fixtures seed. */
    private const int SEEDED_MOVEMENTS = 68;
    private const int SEEDED_FAMILIES = 41;

    private MovementProviderGateway $movementProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movementProviderGateway = self::getContainer()->get(MovementProviderGateway::class);

        $this->loadFixtures(MovementFixtures::class);
    }

    // ---------------------------------------------------------------- families

    public function testItListsEveryFamily(): void
    {
        self::assertCount(self::SEEDED_FAMILIES, self::getContainer()->get(ListMovementFamiliesForAdminUseCase::class)->execute());
    }

    public function testItRefusesAFamilyNameAlreadyTakenIgnoringCase(): void
    {
        try {
            self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute(new CreateMovementFamilyDataInput('bench press'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MovementFamilyNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItDeletesAnEmptyFamily(): void
    {
        $family = self::getContainer()->get(CreateMovementFamilyUseCase::class)->execute(new CreateMovementFamilyDataInput('Clean'));

        self::getContainer()->get(DeleteMovementFamilyUseCase::class)->execute($family->id);

        self::assertNotContains('Clean', array_map(
            static fn ($output) => $output->name,
            self::getContainer()->get(ListMovementFamiliesForAdminUseCase::class)->execute(),
        ));
    }

    public function testItRefusesToDeleteAFamilyHoldingMovements(): void
    {
        try {
            self::getContainer()->get(DeleteMovementFamilyUseCase::class)->execute($this->family(MovementFamilyFixtures::PLANK)->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMovementFamilyUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(MovementFamilyUnusedConstraint::IN_USE, $exception->violations['id']);
        }
    }

    // --------------------------------------------------------------- the list

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
        self::getContainer()->get(DeactivateMovementUseCase::class)->execute($this->movement(MovementFixtures::BENCH_PRESS_BARBELL)->id ?? 0);

        self::assertSame(['Bench press (dumbbell)'], $this->names(new ListMovementsForAdminDataInput(
            isActive: true,
            movementFamilyId: $this->family(MovementFamilyFixtures::BENCH_PRESS)->id,
        )));
    }

    // ---------------------------------------------------------- creating one

    public function testItCreatesAMovement(): void
    {
        $output = self::getContainer()->get(CreateMovementUseCase::class)->execute($this->create('Floor press (dumbbell)'));

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
        $output = self::getContainer()->get(CreateMovementUseCase::class)->execute($this->create('Diamond push-up', equipmentIds: []));

        self::assertSame([], $output->equipments);
    }

    public function testItRefusesANameAlreadyTakenIgnoringCase(): void
    {
        $this->assertCreateRefused($this->create('bench press (BARBELL)'), 'name', MovementNameAvailableConstraint::NAME_ALREADY_USED);
    }

    public function testItRefusesAnInactiveFamily(): void
    {
        $family = $this->family(MovementFamilyFixtures::BENCH_PRESS);
        self::getContainer()->get(DeactivateMovementFamilyUseCase::class)->execute($family->id ?? 0);

        $this->assertCreateRefused($this->create('Floor press (dumbbell)'), 'movementFamilyId', MovementFamilyUsableConstraint::MOVEMENT_FAMILY_INACTIVE);
    }

    public function testItRefusesAPrimaryMuscleInAnInactiveGroup(): void
    {
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)
            ->execute($this->getReference(MuscleGroupFixtures::CHEST, MuscleGroupDataModel::class)->id ?? 0);

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
        self::getContainer()->get(DeactivateEquipmentUseCase::class)->execute($dumbbell->id ?? 0);

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

    // ---------------------------------------------------------- changing one

    public function testItReconfiguresAMovement(): void
    {
        $pushUp = $this->movement(MovementFixtures::PUSH_UP);
        $vest = $this->equipmentNamed('Weighted vest');

        $output = self::getContainer()->get(UpdateMovementUseCase::class)->execute($pushUp->id ?? 0, new UpdateMovementDataInput(
            name: 'Push-up (weighted vest)',
            movementFamilyId: $pushUp->movementFamily->id ?? 0,
            primaryMuscleId: $pushUp->primaryMuscle->id ?? 0,
            equipmentIds: [$vest->id ?? 0],
            tracksReps: true,
            tracksWeight: true,
        ));

        self::assertSame('Push-up (weighted vest)', $output->name);
        self::assertSame([], $output->secondaryMuscles);
        self::assertSame(['Weighted vest'], array_map(static fn ($equipment) => $equipment->name, $output->equipments));
        self::assertTrue($output->tracksWeight);

        $reread = $this->movementProviderGateway->findOneCommonById($output->id);
        self::assertNotNull($reread);
        self::assertCount(0, $reread->secondaryMuscles);
        self::assertCount(1, $reread->equipments);
    }

    /** What a movement already has and has been retired since stays on it when it changes otherwise. */
    public function testWhatAMovementAlreadyHasMayStayRetired(): void
    {
        $bench = $this->movement(MovementFixtures::BENCH_PRESS_BARBELL);
        $barbell = $this->getReference(EquipmentFixtures::BARBELL, EquipmentDataModel::class);
        self::getContainer()->get(DeactivateEquipmentUseCase::class)->execute($barbell->id ?? 0);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)
            ->execute($this->getReference(MuscleGroupFixtures::CHEST, MuscleGroupDataModel::class)->id ?? 0);

        $output = self::getContainer()->get(UpdateMovementUseCase::class)->execute($bench->id ?? 0, $this->updateFrom($bench, 'Bench press (Olympic barbell)'));

        self::assertSame('Bench press (Olympic barbell)', $output->name);
        self::assertFalse($output->primaryMuscle->muscleGroupIsActive);
    }

    public function testItRefusesToTakeOnARetiredEquipment(): void
    {
        $pushUp = $this->movement(MovementFixtures::PUSH_UP);
        $vest = $this->equipmentNamed('Weighted vest');
        self::getContainer()->get(DeactivateEquipmentUseCase::class)->execute($vest->id ?? 0);

        try {
            self::getContainer()->get(UpdateMovementUseCase::class)->execute($pushUp->id ?? 0, new UpdateMovementDataInput(
                name: 'Push-up',
                movementFamilyId: $pushUp->movementFamily->id ?? 0,
                primaryMuscleId: $pushUp->primaryMuscle->id ?? 0,
                equipmentIds: [$vest->id ?? 0],
                tracksReps: true,
            ));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MovementEquipmentsConstraint::EQUIPMENT_INACTIVE, $exception->violations['equipmentIds']);
        }
    }

    public function testItFailsOnAnUnknownMovement(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        self::getContainer()->get(DeleteMovementUseCase::class)->execute(123456789);
    }

    public function testItDeletesAMovement(): void
    {
        $id = $this->movement(MovementFixtures::FARMER_WALK_DUMBBELL)->id ?? 0;

        self::getContainer()->get(DeleteMovementUseCase::class)->execute($id);

        self::assertNull($this->movementProviderGateway->findOneCommonById($id));
    }

    /** A movement a workout logged stays in the history: deleting it is refused, anyone's workout. */
    public function testItRefusesToDeleteAMovementAWorkoutLogged(): void
    {
        $this->loadFixtures(MovementFixtures::class, UserFixtures::class);
        $id = $this->movement(MovementFixtures::PUSH_UP)->id ?? 0;
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;

        $workout = self::getContainer()->get(StartWorkoutUseCase::class)->execute($aliceId, new StartWorkoutDataInput());
        self::getContainer()->get(AddWorkoutBlockUseCase::class)->execute($aliceId, $workout->id, new AddWorkoutBlockDataInput([$id]));

        try {
            self::getContainer()->get(DeleteMovementUseCase::class)->execute($id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMovementUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([MovementUnusedConstraint::IN_USE], $exception->violations['id']);
        }
    }

    // -------------------------------------------- what movements hold on to

    public function testAnEquipmentAMovementUsesCannotBeDeleted(): void
    {
        try {
            self::getContainer()->get(DeleteEquipmentUseCase::class)
                ->execute($this->getReference(EquipmentFixtures::BENCH, EquipmentDataModel::class)->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteEquipmentUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(EquipmentUnusedConstraint::IN_USE, $exception->violations['id']);
        }
    }

    public function testAnEquipmentNoMovementUsesCanBeDeleted(): void
    {
        $foamRoller = $this->equipmentNamed('Foam roller');

        self::getContainer()->get(DeleteEquipmentUseCase::class)->execute($foamRoller->id ?? 0);

        self::assertNull(self::getContainer()->get(\App\Domain\Gateway\Provider\EquipmentProviderGateway::class)->findOneByName('Foam roller'));
    }

    /** Held as a secondary muscle only, it is in use all the same. */
    public function testAMuscleAMovementTargetsCannotBeDeleted(): void
    {
        try {
            self::getContainer()->get(DeleteMuscleUseCase::class)->execute($this->muscleNamed('Front delts')->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMuscleUseCase::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleUnusedConstraint::IN_USE, $exception->violations['id']);
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Floor press on dumbbells, in the Bench press family: a valid creation, varied one field at a time.
     *
     * @param list<int>|null $secondaryMuscleIds
     * @param list<int>|null $equipmentIds
     */
    private function create(
        string $name,
        ?array $secondaryMuscleIds = null,
        ?array $equipmentIds = null,
        bool $tracksReps = true,
    ): CreateMovementDataInput {
        return new CreateMovementDataInput(
            name: $name,
            movementFamilyId: $this->family(MovementFamilyFixtures::BENCH_PRESS)->id ?? 0,
            primaryMuscleId: $this->muscleNamed('Mid chest')->id ?? 0,
            description: 'Lying on the floor, press the dumbbells up.',
            videoUrl: 'https://example.com/floor-press',
            secondaryMuscleIds: $secondaryMuscleIds ?? [$this->muscleNamed('Triceps')->id ?? 0],
            equipmentIds: $equipmentIds ?? [$this->getReference(EquipmentFixtures::DUMBBELL, EquipmentDataModel::class)->id ?? 0],
            tracksReps: $tracksReps,
            tracksWeight: true,
        );
    }

    /** The movement as it is, renamed. */
    private function updateFrom(MovementDataModel $movement, string $name): UpdateMovementDataInput
    {
        $reread = $this->movementProviderGateway->findOneCommonById($movement->id ?? 0);
        self::assertNotNull($reread);

        return new UpdateMovementDataInput(
            name: $name,
            movementFamilyId: $reread->movementFamily->id ?? 0,
            primaryMuscleId: $reread->primaryMuscle->id ?? 0,
            secondaryMuscleIds: array_values(array_map(static fn (MuscleDataModel $muscle): int => $muscle->id ?? 0, $reread->secondaryMuscles->toArray())),
            equipmentIds: array_values(array_map(static fn (EquipmentDataModel $equipment): int => $equipment->id ?? 0, $reread->equipments->toArray())),
            tracksReps: $reread->tracksReps,
            tracksWeight: $reread->tracksWeight,
        );
    }

    private function assertCreateRefused(CreateMovementDataInput $input, string $field, string $code): void
    {
        try {
            self::getContainer()->get(CreateMovementUseCase::class)->execute($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey($field, $exception->violations);
            self::assertContains($code, $exception->violations[$field]);
        }
    }

    /** @return list<\App\Domain\DTO\Output\Workout\MovementDataOutput> */
    private function list(ListMovementsForAdminDataInput $input): array
    {
        return self::getContainer()->get(ListMovementsForAdminUseCase::class)->execute($input);
    }

    /** @return list<string> */
    private function names(ListMovementsForAdminDataInput $input): array
    {
        return array_map(static fn ($movement) => $movement->name, $this->list($input));
    }

    private function only(ListMovementsForAdminDataInput $input, string $name): \App\Domain\DTO\Output\Workout\MovementDataOutput
    {
        foreach ($this->list($input) as $movement) {
            if ($name === $movement->name) {
                return $movement;
            }
        }

        self::fail(sprintf('"%s" is not in the list.', $name));
    }

    private function family(string $reference): MovementFamilyDataModel
    {
        return $this->getReference($reference, MovementFamilyDataModel::class);
    }

    private function movement(string $reference): MovementDataModel
    {
        return $this->getReference($reference, MovementDataModel::class);
    }

    private function muscleNamed(string $name): MuscleDataModel
    {
        $muscle = self::getContainer()->get(\App\Domain\Gateway\Provider\MuscleProviderGateway::class)->findOneByName($name);
        self::assertNotNull($muscle);

        return $muscle;
    }

    private function equipmentNamed(string $name): EquipmentDataModel
    {
        $equipment = self::getContainer()->get(\App\Domain\Gateway\Provider\EquipmentProviderGateway::class)->findOneByName($name);
        self::assertNotNull($equipment);

        return $equipment;
    }
}
