<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningSlotDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningOccurrenceStateRegistry;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\RecordedEventsTrait;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use App\UseCase\Planning\DeleteTaskPlanningEntryUseCase;
use App\UseCase\Planning\ListTaskPlanningUseCase;
use App\UseCase\Todo\CompleteTaskUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The task planning over some days: the entries, and the dates each gives. */
final class ListTaskPlanningUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RecordedEventsTrait;
    use TaskPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** A week ahead: the seeded entry gives a Monday and a Thursday, both to come, with no task yet. */
    public function testItListsTheEntriesAndTheirDates(): void
    {
        $monday = $this->inDays(7, $this->monday());
        $output = $this->useCase(ListTaskPlanningUseCase::class)->execute($this->currentUser($this->aliceId), new ListPlanningDataInput($monday, $this->inDays(6, $monday)));

        self::assertCount(1, $output->entries);
        $entry = $output->entries[0];
        self::assertSame(['Sortir les poubelles', 'Jaune le lundi, verte le jeudi.', $this->homeId()], [$entry->title, $entry->description, $entry->category?->id]);
        self::assertSame(['weekly', [1, 4], '20:00'], [$entry->schedule->rule, $entry->schedule->weekdays, $entry->schedule->startTime]);
        self::assertSame([$monday, $this->inDays(3, $monday)], array_map(static fn ($slot) => $slot->day, $output->slots));
        self::assertSame([PlanningOccurrenceStateRegistry::TO_COME, PlanningOccurrenceStateRegistry::TO_COME], array_map(static fn ($slot) => $slot->state, $output->slots));
        self::assertSame([null, null], array_map(static fn ($slot) => $slot->taskId, $output->slots));
    }

    /** Today's task is open once made, then done; the days gone by without a task are not shown; the days ahead are to come. */
    public function testTodaysDateFollowsItsTask(): void
    {
        $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId());
        $this->withHandlers(fn () => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->everyDay()));

        $today = $this->todaysSlot();
        self::assertSame(PlanningOccurrenceStateRegistry::OPEN, $today?->state);
        self::assertNotNull($today->taskId);

        $this->useCase(CompleteTaskUseCase::class)->execute($this->currentUser($this->aliceId), $today->taskId);

        self::assertSame(PlanningOccurrenceStateRegistry::DONE, $this->todaysSlot()?->state);
        foreach ($this->thisWeek()->slots as $slot) {
            self::assertGreaterThanOrEqual($this->today(), $slot->day);
            if ($slot->day > $this->today()) {
                self::assertSame(PlanningOccurrenceStateRegistry::TO_COME, $slot->state);
            }
        }
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
            $this->useCase(ListTaskPlanningUseCase::class)->execute($this->currentUser($this->aliceId), new ListPlanningDataInput('2026-11-01', '2027-01-01'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['to' => [PlanningRangeConstraint::RANGE_INVALID]], $exception->violations);
        }
    }

    public function testListingStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->thisWeekFor($currentUser),
            // The entries with their categories, then the tasks they made over those days.
            max: 2,
        );
    }

    public function testListingDoesNotGrowWithTheEntries(): void
    {
        $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->thisWeekFor($currentUser),
            fn () => $this->withHandlers(fn () => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->everyDay(categoryId: $this->homeId()))),
        );
    }

    private function thisWeekFor(UserDataModel $currentUser): mixed
    {
        return $this->useCase(ListTaskPlanningUseCase::class)->execute($currentUser, new ListPlanningDataInput($this->monday(), $this->inDays(6, $this->monday())));
    }

    private function todaysSlot(): ?TaskPlanningSlotDataOutput
    {
        foreach ($this->thisWeek()->slots as $slot) {
            if ($slot->day === $this->today()) {
                return $slot;
            }
        }

        return null;
    }
}
