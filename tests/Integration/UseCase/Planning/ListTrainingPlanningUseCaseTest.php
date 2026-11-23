<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningSlotDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\CreateTrainingPlanningEntryUseCase;
use App\UseCase\Planning\ListTrainingPlanningUseCase;
use App\UseCase\Training\StartWorkoutFromRoutineUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The training planning over some days: the entries, and the slots each gives, kept, missed or to come. */
final class ListTrainingPlanningUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use TrainingPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** The seeded entry gives a Tuesday and a Thursday each week, at 18:00 for 1 h 15. */
    public function testItListsTheEntriesAndTheirSlots(): void
    {
        $output = $this->useCase(ListTrainingPlanningUseCase::class)->execute(
            $this->currentUser($this->aliceId), new ListPlanningDataInput('2026-11-30', '2026-12-06'),
        );

        self::assertCount(1, $output->entries);
        $entry = $output->entries[0];
        self::assertSame([$this->pushId(), 'Push'], [$entry->routineId, $entry->routineName]);
        self::assertSame(['weekly', '2026-11-02', [2, 4], '18:00', 75], [$entry->schedule->rule, $entry->schedule->startDate, $entry->schedule->weekdays, $entry->schedule->startTime, $entry->schedule->durationInMinutes]);
        self::assertSame(['2026-12-01', '2026-12-03'], array_map(static fn ($slot) => $slot->day, $output->slots));
        self::assertSame([$entry->id, $entry->id], array_map(static fn ($slot) => $slot->entryId, $output->slots));
    }

    /** Today's slot is to come until a workout of its routine is started; then kept, by that workout. */
    public function testAWorkoutOfTheRoutineStartedTodayKeepsTodaysSlot(): void
    {
        $this->useCase(CreateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->everyDay($this->pushId()));

        self::assertSame(PlanningOccurrenceStateRegistry::TO_COME, $this->todaysSlot()->state);

        $workout = $this->useCase(StartWorkoutFromRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $this->pushId())->workout;

        $slot = $this->todaysSlot();
        self::assertSame(PlanningOccurrenceStateRegistry::KEPT, $slot->state);
        self::assertSame($workout->id, $slot->workoutId);
    }

    /** The days of the week gone by without a workout are missed; those after today, to come. */
    public function testAPastSlotWithoutAWorkoutIsMissed(): void
    {
        $this->useCase(CreateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->everyDay($this->pushId()));

        foreach ($this->thisWeek()->slots as $slot) {
            if ($slot->day < $this->today()) {
                self::assertSame(PlanningOccurrenceStateRegistry::MISSED, $slot->state);
            }
            if ($slot->day > $this->today()) {
                self::assertSame(PlanningOccurrenceStateRegistry::TO_COME, $slot->state);
            }
        }
    }

    public function testSomeoneElsesEntriesAreNotListed(): void
    {
        $output = $this->useCase(ListTrainingPlanningUseCase::class)->execute(
            $this->currentUser($this->bobId), new ListPlanningDataInput('2026-11-30', '2026-12-06'),
        );

        self::assertSame([], $output->entries);
        self::assertSame([], $output->slots);
    }

    public function testARangeTooLongIsRefused(): void
    {
        try {
            $this->useCase(ListTrainingPlanningUseCase::class)->execute($this->currentUser($this->aliceId), new ListPlanningDataInput('2026-11-01', '2027-01-01'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['to' => [PlanningRangeConstraint::RANGE_INVALID]], $exception->violations);
        }
    }

    public function testListingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListTrainingPlanningUseCase::class)->execute($currentUser, new ListPlanningDataInput('2026-11-30', '2026-12-06')),
            // The entries with their routines, then the workouts started from one over those days.
            max: 2,
        );
    }

    public function testListingDoesNotGrowWithTheEntries(): void
    {
        $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListTrainingPlanningUseCase::class)->execute($currentUser, new ListPlanningDataInput('2026-11-30', '2026-12-06')),
            fn () => $this->useCase(CreateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->everyDay($this->pushId(), '07:00')),
        );
    }

    /** Today's slot of the every-day entry: the seeded one falls on two days only, and holds a time. */
    private function todaysSlot(): TrainingPlanningSlotDataOutput
    {
        foreach ($this->thisWeek()->slots as $slot) {
            if ($slot->day === $this->today()) {
                return $slot;
            }
        }

        self::fail('No slot today.');
    }
}
