<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMovementDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Constraint\Training\MovementEquipmentsConstraint;
use App\Domain\Validation\Validator\Training\UpdateMovementValidator;
use App\Fixtures\Training\EquipmentFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateEquipmentUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\UpdateMovementUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Reconfiguring a common movement. */
final class UpdateMovementUseCaseTest extends KernelTestCase
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

    public function testItReconfiguresAMovement(): void
    {
        $pushUp = $this->movement(MovementFixtures::PUSH_UP);
        $vest = $this->equipmentNamed('Weighted vest');

        $output = self::getContainer()->get(UpdateMovementUseCase::class)->execute($this->currentAdmin(), $pushUp->id ?? 0, new UpdateMovementDataInput(
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
        self::getContainer()->get(DeactivateEquipmentUseCase::class)->execute($this->currentAdmin(), $barbell->id ?? 0);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)
            ->execute($this->currentAdmin(), $this->getReference(MuscleGroupFixtures::CHEST, MuscleGroupDataModel::class)->id ?? 0);

        $output = self::getContainer()->get(UpdateMovementUseCase::class)->execute($this->currentAdmin(), $bench->id ?? 0, $this->updateFrom($bench, 'Bench press (Olympic barbell)'));

        self::assertSame('Bench press (Olympic barbell)', $output->name);
        self::assertFalse($output->primaryMuscle->muscleGroupIsActive);
    }

    public function testItRefusesToTakeOnARetiredEquipment(): void
    {
        $pushUp = $this->movement(MovementFixtures::PUSH_UP);
        $vest = $this->equipmentNamed('Weighted vest');
        self::getContainer()->get(DeactivateEquipmentUseCase::class)->execute($this->currentAdmin(), $vest->id ?? 0);

        try {
            self::getContainer()->get(UpdateMovementUseCase::class)->execute($this->currentAdmin(), $pushUp->id ?? 0, new UpdateMovementDataInput(
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

    public function testReconfiguringAMovementStaysWithinItsQueryBudget(): void
    {
        $bench = $this->movement(MovementFixtures::BENCH_PRESS_BARBELL);
        $id = $bench->id ?? 0;
        $input = $this->updateFrom($bench, 'Bench press (Olympic barbell)');

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(UpdateMovementUseCase::class)->execute($currentUser, $id, $input),
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
}
