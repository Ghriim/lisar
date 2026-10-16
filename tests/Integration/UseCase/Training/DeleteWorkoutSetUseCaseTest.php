<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Domain\Validation\Constraint\Training\WorkoutSetInWorkoutConstraint;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteWorkoutSetUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a set from a workout, and whatever it counted for. */
final class DeleteWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testSetsComeInTheOrderTheyWereLoggedAndCanBeRemoved(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);
        $this->addSet($workout, new AddWorkoutSetDataInput(reps: 15));
        $this->addSet($workout, new AddWorkoutSetDataInput(reps: 12));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 10));

        $sets = $workout->blocks[0]->exercises[0]->sets;
        self::assertSame([15, 12, 10], array_map(static fn ($set) => $set->reps, $sets));

        $output = $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $sets[1]->id);

        self::assertSame([15, 10], array_map(static fn ($set) => $set->reps, $output->blocks[0]->exercises[0]->sets));
    }

    public function testASetOfAnotherWorkoutIsNotFound(): void
    {
        $seeded = $this->useCase(GetWorkoutUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class)->id ?? 0,
        );
        $workout = $this->start();

        try {
            $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $seeded->blocks[0]->exercises[0]->sets[0]->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['setId' => [WorkoutSetInWorkoutConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testASetThatDoesNotExistIsNotFound(): void
    {
        $workout = $this->start();

        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, 999999);
    }

    public function testRemovingASetHandsTheRecordBack(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testRemovingASetTakesItOut(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 6, weightInKilograms: 50.0));
        $workout = $this->complete($workout, $this->lastSet($workout));

        $this->useCase(DeleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);
    }

    public function testRemovingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId),
        );
    }
}
