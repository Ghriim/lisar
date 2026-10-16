<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutExerciseDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\UpdateWorkoutExerciseUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a movement of a workout: its note and its rest. */
final class UpdateWorkoutExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testTheRestOfAMovementIsCorrectedThenCleared(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $exerciseId = $workout->blocks[0]->exercises[0]->id;
        $update = fn (UpdateWorkoutExerciseDataInput $input): WorkoutDataOutput => $this->useCase(UpdateWorkoutExerciseUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $exerciseId, $input,
        );

        $corrected = $update(new UpdateWorkoutExerciseDataInput('Coudes serrés', 120));
        self::assertSame(120, $corrected->blocks[0]->exercises[0]->restInSeconds);

        // The window sends both fields: a rest left empty is no rest.
        $cleared = $update(new UpdateWorkoutExerciseDataInput('Coudes serrés'));
        self::assertNull($cleared->blocks[0]->exercises[0]->restInSeconds);
        self::assertSame('Coudes serrés', $cleared->blocks[0]->exercises[0]->note);
    }

    public function testUpdatingAMovementStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $exerciseId = $workout->blocks[0]->exercises[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateWorkoutExerciseUseCase::class)->execute($currentUser, $workout->id, $exerciseId, new UpdateWorkoutExerciseDataInput('Coudes serrés', 90)),
        );
    }
}
