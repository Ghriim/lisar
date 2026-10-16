<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Unticking a set: it is to do again, and counts for nothing until ticked anew. */
final class UncompleteWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testUntickingASetHandsTheRecordBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $output = $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        self::assertSame([], $this->lastSet($output)->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testUntickingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;
        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId),
        );
    }
}
