<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Domain\DTO\Output\Training\WorkoutSetDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Training\PersonalBestKindRegistry as Kind;
use App\Domain\Validation\Constraint\Training\WorkoutInProgressConstraint;
use App\Domain\Validation\Validator\Training\CompleteWorkoutSetValidator;
use App\Domain\Validation\Validator\Training\UncompleteWorkoutSetValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\SetTypeFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\CompleteWorkoutSetUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\UncompleteWorkoutSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Ticking a set as done: from then on it counts, in its workout's summary and against the personal bests. */
final class CompleteWorkoutSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** During a workout a set is logged before it is done, and ticked once it is. */
    public function testASetIsLoggedUndoneThenTicked(): void
    {
        $workout = $this->addSet($this->addBlock($this->start(), MovementFixtures::PUSH_UP), new AddWorkoutSetDataInput(reps: 10));
        $setId = $workout->blocks[0]->exercises[0]->sets[0]->id;
        self::assertFalse($workout->blocks[0]->exercises[0]->sets[0]->isComplete);

        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);
        // Ticking twice changes nothing.
        $this->useCase(CompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);

        // Read back from the database, not from what the use case held.
        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);
        self::assertTrue($reread->blocks[0]->exercises[0]->sets[0]->isComplete);

        $output = $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $setId);
        self::assertFalse($output->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    public function testAFinishedWorkoutHasNothingToTickOrUntick(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);
        $output = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0);
        $setId = $output->blocks[0]->exercises[0]->sets[0]->id;

        $refusals = [
            CompleteWorkoutSetUseCase::class => CompleteWorkoutSetValidator::ERROR_CODE,
            UncompleteWorkoutSetUseCase::class => UncompleteWorkoutSetValidator::ERROR_CODE,
        ];
        foreach ($refusals as $useCase => $errorCode) {
            try {
                $this->useCase($useCase)->execute($this->currentUser($this->idOf($this->alice)), $output->id, $setId);
                self::fail('Expected ValidationException');
            } catch (ValidationException $exception) {
                self::assertSame($errorCode, $exception->errorCode);
                self::assertSame([WorkoutInProgressConstraint::FINISHED], $exception->violations['workout']);
            }
        }

        $reread = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $output->id);
        self::assertTrue($reread->blocks[0]->exercises[0]->sets[0]->isComplete);
    }

    public function testTickingASetBeatsTheRecordAtOnce(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));

        $output = $this->complete($workout, $this->lastSet($workout));

        self::assertContains(Kind::MAX_WEIGHT, $this->kinds($this->lastSet($output)));
        $record = $this->record($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT);
        self::assertSame(70.0, $record?->current->value);
        self::assertSame([60.0, 70.0], array_map(static fn ($row) => $row->value, $record?->progression ?? []));
    }

    /** A warm-up is light on purpose: however heavy, it sets no record. */
    public function testASetOfATypeThatDoesNotCountBeatsNothing(): void
    {
        $workout = $this->benchWorkout();
        $workout = $this->addSet($workout, new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 100.0, setTypeId: $this->setTypeId(SetTypeFixtures::WARM_UP)));

        $output = $this->complete($workout, $this->lastSet($workout));

        self::assertSame([], $this->lastSet($output)->personalBests);
        self::assertSame(60.0, $this->current($this->board(), MovementFixtures::BENCH_PRESS_BARBELL, Kind::MAX_WEIGHT));
    }

    public function testASetCountsOnceTickedAndNoLongerOnceUnticked(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 8, weightInKilograms: 50.0));
        self::assertSame(0, $this->summaryOf($workout->id)->setCount);

        $workout = $this->complete($workout, $this->lastSet($workout));
        $summary = $this->summaryOf($workout->id);
        self::assertSame(1, $summary->setCount);
        self::assertSame(400.0, (float) $summary->volumeInKilograms);

        $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);
        $summary = $this->summaryOf($workout->id);
        self::assertSame(0, $summary->setCount);
        self::assertNull($summary->volumeInKilograms);
    }

    public function testTheRecordsASetBeatsAreCountedAndHandedBack(): void
    {
        $workout = $this->addSet($this->benchWorkout(), new AddWorkoutSetDataInput(reps: 5, weightInKilograms: 70.0));
        $workout = $this->complete($workout, $this->lastSet($workout));
        $beaten = count($this->lastSet($workout)->personalBests);
        self::assertGreaterThan(0, $beaten);
        self::assertSame($beaten, $this->summaryOf($workout->id)->personalBestCount);

        $this->useCase(UncompleteWorkoutSetUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id, $this->lastSet($workout)->id);

        self::assertSame(0, $this->summaryOf($workout->id)->personalBestCount);
    }

    public function testTickingASetStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $setId = $workout->blocks[0]->exercises[0]->sets[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(CompleteWorkoutSetUseCase::class)->execute($currentUser, $workout->id, $setId),
        );
    }

    /** @return list<string> */
    private function kinds(WorkoutSetDataOutput $set): array
    {
        return array_map(static fn ($record) => $record->kind, $set->personalBests);
    }
}
