<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Output\Planning\HabitPlanningSlotDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Habits\CompleteHabitUseCase;
use App\UseCase\Habits\UnsubscribeHabitUseCase;
use App\UseCase\Planning\CreateHabitPlanningEntryUseCase;
use App\UseCase\Planning\DeleteHabitPlanningEntryUseCase;
use App\UseCase\Planning\ListHabitPlanningUseCase;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The habit planning over some days: the entries, the dates each gives, and the days kept unplanned. */
final class ListHabitPlanningUseCaseTest extends KernelTestCase
{
    use HabitPlanningTestTrait;
    use QueryCountTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** The seeded entry gives a Monday, a Wednesday and a Friday each week; the walk, with no entry, is not there. */
    public function testItListsTheEntriesAndTheirDates(): void
    {
        $output = $this->useCase(ListHabitPlanningUseCase::class)->execute(
            $this->currentUser($this->aliceId), new ListPlanningDataInput('2026-11-30', '2026-12-06'),
        );

        self::assertCount(1, $output->entries);
        $entry = $output->entries[0];
        self::assertSame([$this->readingId(), 'Lire'], [$entry->habitId, $entry->habitName]);
        self::assertSame(['weekly', '2026-11-02', [1, 3, 5], null], [$entry->schedule->rule, $entry->schedule->startDate, $entry->schedule->weekdays, $entry->schedule->startTime]);
        self::assertSame(['2026-11-30', '2026-12-02', '2026-12-04'], array_map(static fn ($slot) => $slot->day, $output->slots));
        self::assertSame([$entry->id, $entry->id, $entry->id], array_map(static fn ($slot) => $slot->entryId, $output->slots));
        self::assertSame([$this->readingId(), $this->readingId(), $this->readingId()], array_map(static fn ($slot) => $slot->habitId, $output->slots));
    }

    /** Today's date is to come until the habit is kept; then kept. The days gone by without it are missed. */
    public function testTodaysDateIsKeptByKeepingTheHabit(): void
    {
        $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());
        $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->weekly($this->readingId()));

        self::assertSame(PlanningOccurrenceStateRegistry::TO_COME, $this->todaysSlot()?->state);

        $this->useCase(CompleteHabitUseCase::class)->execute($this->currentUser($this->aliceId), $this->readingId());

        self::assertSame(PlanningOccurrenceStateRegistry::KEPT, $this->todaysSlot()?->state);
        foreach ($this->thisWeek()->slots as $slot) {
            if ($slot->day < $this->today()) {
                self::assertSame(PlanningOccurrenceStateRegistry::MISSED, $slot->state);
            }
            if ($slot->day > $this->today()) {
                self::assertSame(PlanningOccurrenceStateRegistry::TO_COME, $slot->state);
            }
        }
    }

    /** Planned on no day of this week, the habit kept today is a bonus: kept unplanned, with no entry. */
    public function testAHabitKeptOnADayNotPlannedIsKeptUnplanned(): void
    {
        $this->useCase(DeleteHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());
        $tomorrow = (int) (new DateTimeImmutable($this->today()))->modify('+1 day')->format('N');
        $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->weekly($this->readingId(), [$tomorrow]));

        $this->useCase(CompleteHabitUseCase::class)->execute($this->currentUser($this->aliceId), $this->readingId());

        $slot = $this->todaysSlot();
        self::assertSame(PlanningOccurrenceStateRegistry::KEPT_UNPLANNED, $slot?->state);
        self::assertNull($slot->entryId);
        self::assertSame($this->readingId(), $slot->habitId);
    }

    public function testADroppedHabitIsNotListed(): void
    {
        $this->useCase(UnsubscribeHabitUseCase::class)->execute($this->currentUser($this->aliceId), $this->walkId());
        $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->weekly($this->readingId()));

        // The entry of a habit dropped since — its handler has not run here — is left out all the same.
        $this->useCase(UnsubscribeHabitUseCase::class)->execute($this->currentUser($this->aliceId), $this->readingId());

        self::assertSame([], $this->thisWeek()->entries);
    }

    public function testSomeoneElsesEntriesAreNotListed(): void
    {
        $output = $this->thisWeek($this->bobId);

        self::assertSame([], $output->entries);
        self::assertSame([], $output->slots);
    }

    public function testARangeTooLongIsRefused(): void
    {
        try {
            $this->useCase(ListHabitPlanningUseCase::class)->execute($this->currentUser($this->aliceId), new ListPlanningDataInput('2026-11-01', '2027-01-01'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['to' => [PlanningRangeConstraint::RANGE_INVALID]], $exception->violations);
        }
    }

    public function testListingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListHabitPlanningUseCase::class)->execute($currentUser, new ListPlanningDataInput('2026-11-30', '2026-12-06')),
            // The entries with their subscriptions and habits, then the habit's days over those days.
            max: 2,
        );
    }

    public function testListingDoesNotGrowWithTheEntries(): void
    {
        $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(ListHabitPlanningUseCase::class)->execute($currentUser, new ListPlanningDataInput('2026-11-30', '2026-12-06')),
            fn () => $this->useCase(CreateHabitPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->weekly($this->walkId(), startTime: '07:00')),
        );
    }

    private function todaysSlot(): ?HabitPlanningSlotDataOutput
    {
        foreach ($this->thisWeek()->slots as $slot) {
            if ($slot->day === $this->today()) {
                return $slot;
            }
        }

        return null;
    }
}
