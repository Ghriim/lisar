<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a workout, in progress or finished, and what it counted for: the habit it kept, the records it held. */
final class DeleteWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testAbandoningDeletesTheWorkoutInProgress(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);

        self::assertNull(self::getContainer()->get(WorkoutProviderGateway::class)->findOneById($workout->id));
    }

    public function testAbandoningAWorkoutHandsItsRecordsBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(DeleteWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id);

        $board = $this->board();
        self::assertSame(60.0, $this->current($board, MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
        self::assertSame(7.0, $this->session($board, Kind::MAX_SESSION_SETS)?->current->value);
    }

    public function testDeletingAWorkoutStaysWithinItsQueryBudget(): void
    {
        $workoutId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutUseCase::class)->execute($currentUser, $workoutId),
        );
    }
}
