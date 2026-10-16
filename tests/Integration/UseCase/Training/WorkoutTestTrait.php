<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\DTO\Output\Training\PersonalBestBoardDataOutput;
use App\Domain\DTO\Output\Training\PersonalBestRecordDataOutput;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\DTO\Output\Training\WorkoutSetDataOutput;
use App\Domain\Gateway\Provider\Training\WorkoutSummaryProviderGateway;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\AddWorkoutSetUseCase;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\CopyWorkoutUseCase;
use App\UseCase\Training\FinishWorkoutUseCase;
use App\UseCase\Training\ListPersonalBestsUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * What the workout use cases' tests share: the seeded workouts, Alice and Bob, the steps that build
 * a workout up — start it, add a block, log a set, tick it, finish — and the reads that check what
 * followed: the personal bests board and a workout's summary.
 */
trait WorkoutTestTrait
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

    private UserDataModel $alice;
    private UserDataModel $bob;
    private int $aliceId;

    /** Alice's finished « Push », and Bob, a deactivated account, to be refused someone else's workout. */
    private function loadWorkoutFixtures(): void
    {
        $this->loadFixtures(WorkoutFixtures::class);

        $this->alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $this->bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $this->aliceId = $this->alice->id ?? 0;
    }

    /** The seeded « Push » started again: a bench block, a push-up and farmer walk superset, every set still to do. */
    private function inProgressPush(): WorkoutDataOutput
    {
        return $this->copy(WorkoutFixtures::ALICE_FINISHED)->workout;
    }

    private function start(?string $name = null): WorkoutDataOutput
    {
        return $this->useCase(StartWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), new StartWorkoutDataInput($name));
    }

    private function copy(string $workoutReference): WorkoutCopyDataOutput
    {
        $workout = $this->getReference($workoutReference, WorkoutDataModel::class);

        return $this->useCase(CopyWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0);
    }

    /** A workout started with a single bench press block. */
    private function benchWorkout(): WorkoutDataOutput
    {
        $workout = $this->useCase(StartWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), new StartWorkoutDataInput());

        return $this->useCase(AddWorkoutBlockUseCase::class)->execute(
            $this->currentUser($this->aliceId), $workout->id, new AddWorkoutBlockDataInput([new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL))]),
        );
    }

    private function addBlock(WorkoutDataOutput $workout, string ...$movementReferences): WorkoutDataOutput
    {
        $exercises = array_values(array_map(fn (string $reference) => new AddWorkoutBlockExerciseDataInput($this->movementId($reference)), $movementReferences));

        return $this->useCase(AddWorkoutBlockUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, new AddWorkoutBlockDataInput($exercises));
    }

    /** On the first movement of the first block. */
    private function addSet(WorkoutDataOutput $workout, AddWorkoutSetDataInput $input): WorkoutDataOutput
    {
        return $this->useCase(AddWorkoutSetUseCase::class)->execute(
            $this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->exercises[0]->id, $input,
        );
    }

    /** On the first movement of the first block, then ticked as done: the last set there. */
    private function addDoneSet(WorkoutDataOutput $workout, AddWorkoutSetDataInput $input): WorkoutDataOutput
    {
        $workout = $this->addSet($workout, $input);
        $set = $workout->blocks[0]->exercises[0]->sets[array_key_last($workout->blocks[0]->exercises[0]->sets)];

        return $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $set->id);
    }

    private function complete(WorkoutDataOutput $workout, WorkoutSetDataOutput $set): WorkoutDataOutput
    {
        return $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $set->id);
    }

    /** The last set of the first movement of the first block. */
    private function lastSet(WorkoutDataOutput $workout): WorkoutSetDataOutput
    {
        $sets = $workout->blocks[0]->exercises[0]->sets;
        if ([] === $sets) {
            throw new LogicException('No set on the first movement.');
        }

        return $sets[array_key_last($sets)];
    }

    private function finish(WorkoutDataOutput $workout): WorkoutDataOutput
    {
        return $this->useCase(FinishWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
    }

    /** @return list<string> */
    private function movementNames(WorkoutDataOutput $workout, int $blockIndex): array
    {
        return array_map(static fn ($exercise) => $exercise->movement->name, $workout->blocks[$blockIndex]->exercises);
    }

    private function board(): PersonalBestBoardDataOutput
    {
        return $this->useCase(ListPersonalBestsUseCase::class)->execute($this->currentUser($this->aliceId));
    }

    private function record(PersonalBestBoardDataOutput $board, string $movementReference, string $kind, ?float $tier = null): ?PersonalBestRecordDataOutput
    {
        $movementId = $this->getReference($movementReference, MovementDataModel::class)->id;
        foreach ($board->movements as $movement) {
            if ($movementId !== $movement->movementId) {
                continue;
            }
            foreach ($movement->records as $record) {
                if ($kind === $record->kind && $tier === $record->tier) {
                    return $record;
                }
            }
        }

        return null;
    }

    private function current(PersonalBestBoardDataOutput $board, string $movementReference, string $kind, ?float $tier = null): ?float
    {
        return $this->record($board, $movementReference, $kind, $tier)?->current->value;
    }

    private function session(PersonalBestBoardDataOutput $board, string $kind): ?PersonalBestRecordDataOutput
    {
        foreach ($board->sessions as $record) {
            if ($kind === $record->kind) {
                return $record;
            }
        }

        return null;
    }

    /** Read back from the database, not from what the request holds in memory. */
    private function summaryOf(int $workoutId): WorkoutSummaryDataModel
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $summary = self::getContainer()->get(WorkoutSummaryProviderGateway::class)->findOneForWorkout(
            $entityManager->getReference(WorkoutDataModel::class, $workoutId),
        );
        if (null === $summary) {
            throw new LogicException('The workout has no summary.');
        }

        return $summary;
    }

    private function movementId(string $reference): int
    {
        return $this->getReference($reference, MovementDataModel::class)->id ?? 0;
    }

    private function setTypeId(string $reference): int
    {
        return $this->getReference($reference, SetTypeDataModel::class)->id ?? 0;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function useCase(string $class): object
    {
        return self::getContainer()->get($class);
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }
}
