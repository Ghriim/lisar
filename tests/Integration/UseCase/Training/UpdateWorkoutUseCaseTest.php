<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutDataInput;
use App\Domain\DTO\Input\Training\UpdateWorkoutExerciseDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\UpdateWorkoutExerciseUseCase;
use App\UseCase\Training\UpdateWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Naming, noting and rating a workout, in progress or finished. */
final class UpdateWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItNotesAWorkoutAndAMovement(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        $this->useCase(UpdateWorkoutExerciseUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->exercises[0]->id, new UpdateWorkoutExerciseDataInput('Coudes serrés'),
        );
        $output = $this->useCase(UpdateWorkoutUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, new UpdateWorkoutDataInput('Haut du corps', 'Bonne énergie', 5),
        );

        self::assertSame('Haut du corps', $output->name);
        self::assertSame('Bonne énergie', $output->note);
        self::assertSame(5, $output->feeling);
        self::assertSame('Coudes serrés', $output->blocks[0]->exercises[0]->note);
        self::assertSame($workout->startedAt, $output->startedAt);
    }

    public function testUpdatingAWorkoutStaysWithinItsQueryBudget(): void
    {
        $workoutId = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateWorkoutUseCase::class)->execute($currentUser, $workoutId, new UpdateWorkoutDataInput('Push lourd', 'Bonne séance', 4)),
        );
    }
}
