<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMovementDataInput;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Fixtures\Training\EquipmentFixtures;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\Admin\CreateMovementUseCase;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use App\UseCase\Training\ListWorkoutMovementsUseCase;
use App\UseCase\Training\ListWorkoutSetTypesUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The movements a workout can take: those still on offer. */
final class ListWorkoutMovementsUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testThePickersOfferOnlyWhatIsLive(): void
    {
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movementId(MovementFixtures::PUSH_UP));
        $this->useCase(DeactivateSetTypeUseCase::class)->execute($this->currentAdmin(), $this->setTypeId(SetTypeFixtures::DROPSET));

        $movements = array_map(static fn ($movement) => $movement->name, $this->useCase(ListWorkoutMovementsUseCase::class)->execute($this->anyCurrentUser()));
        $setTypes = array_map(static fn ($setType) => $setType->name, $this->useCase(ListWorkoutSetTypesUseCase::class)->execute($this->anyCurrentUser()));

        self::assertContains('Bench press (barbell)', $movements);
        self::assertNotContains('Push-up', $movements);
        self::assertSame(['Back-off', 'Échauffement', 'Échec', 'Travail'], $setTypes);
    }

    public function testListingTheMovementsOnOfferStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutMovementsUseCase::class)->execute($currentUser),
        );
    }

    public function testListingTheMovementsOnOfferDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutMovementsUseCase::class)->execute($currentUser),
            function (): void {
                foreach (['Floor press', 'Close-grip press', 'Spoto press'] as $name) {
                    $this->useCase(CreateMovementUseCase::class)->execute($this->currentAdmin(), $this->newMovement($name));
                }
            },
        );

        self::assertCount(count($fewer) + 3, $more);
    }

    private function newMovement(string $name): CreateMovementDataInput
    {
        $muscle = self::getContainer()->get(MuscleProviderGateway::class)->findOneByName('Mid chest');
        self::assertNotNull($muscle);

        return new CreateMovementDataInput(
            name: $name,
            movementFamilyId: $this->getReference(MovementFamilyFixtures::BENCH_PRESS, MovementFamilyDataModel::class)->id ?? 0,
            primaryMuscleId: $muscle->id ?? 0,
            equipmentIds: [$this->getReference(EquipmentFixtures::DUMBBELL, EquipmentDataModel::class)->id ?? 0],
            tracksReps: true,
            tracksWeight: true,
        );
    }
}
