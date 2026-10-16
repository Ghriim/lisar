<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutExerciseDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\AddWorkoutExerciseUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A movement joining an existing block of a workout, which makes it a superset. */
final class AddWorkoutExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testAMovementJoinsAnExistingBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->useCase(AddWorkoutExerciseUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->id,
            new AddWorkoutExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
        );

        self::assertSame(['Bench press (barbell)', 'Push-up'], $this->movementNames($output, 0));
    }

    public function testAMovementJoinsABlockWithItsRest(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->useCase(AddWorkoutExerciseUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->id,
            new AddWorkoutExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP), 75),
        );

        self::assertSame(75, $output->blocks[0]->exercises[1]->restInSeconds);
    }

    public function testAddingAMovementStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $blockId = $workout->blocks[1]->id;
        $movementId = $this->movementId(MovementFixtures::BENCH_PRESS_BARBELL);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(AddWorkoutExerciseUseCase::class)->execute($currentUser, $workout->id, $blockId, new AddWorkoutExerciseDataInput($movementId)),
        );
    }
}
