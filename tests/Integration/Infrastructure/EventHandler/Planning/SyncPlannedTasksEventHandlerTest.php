<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryCreatedEvent;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryDeletedEvent;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryUpdatedEvent;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningEntryDataOutput;
use App\Infrastructure\EventHandler\Planning\SyncPlannedTasksEventHandler;
use App\Tests\Integration\Infrastructure\EventHandler\EventHandlerTestTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\UseCase\Planning\TaskPlanningTestTrait;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use App\UseCase\Planning\DeleteTaskPlanningEntryUseCase;
use App\UseCase\Planning\UpdateTaskPlanningEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The tasks a task entry makes follow its writes. */
final class SyncPlannedTasksEventHandlerTest extends KernelTestCase
{
    use EventHandlerTestTrait;
    use QueryCountTrait;
    use TaskPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** A recurring rule falling today makes today's task at once, and nothing ahead. */
    public function testCreatingARecurringEntryMakesTodaysTask(): void
    {
        $this->handle($this->eventRaisedBy(TaskPlanningEntryCreatedEvent::class, fn () => $this->create($this->everyDay(categoryId: $this->homeId()))));

        $tasks = $this->plannedTasks();
        self::assertCount(1, $tasks);
        self::assertSame(['Arroser les plantes', $this->homeId(), $this->today(), $this->today()], [$tasks[0]->title, $tasks[0]->category?->id, $tasks[0]->dueDate?->format('Y-m-d'), $tasks[0]->plannedDate?->format('Y-m-d')]);
    }

    /** A single date's task is made at once, to be seen coming. */
    public function testCreatingASingleDateMakesItsTaskAtOnce(): void
    {
        $this->handle($this->eventRaisedBy(TaskPlanningEntryCreatedEvent::class, fn () => $this->create($this->singleDate($this->inDays(10)))));

        self::assertSame([$this->inDays(10)], $this->plannedDates());
    }

    /** Moved, a single date's task made ahead goes and is made again, from the new model. */
    public function testChangingASingleDateRemakesItsTask(): void
    {
        $entry = $this->withHandlers(fn () => $this->create($this->singleDate($this->inDays(10))));

        $this->handle($this->eventRaisedBy(TaskPlanningEntryUpdatedEvent::class, fn () => $this->update($entry, $this->singleDate($this->inDays(12), 'Déclarer les impôts — en ligne'))));

        $tasks = $this->plannedTasks();
        self::assertSame([$this->inDays(12)], $this->plannedDates());
        self::assertSame('Déclarer les impôts — en ligne', $tasks[0]->title);
    }

    /** Today's task has fallen: a change of model leaves it as it was, and makes no second one. */
    public function testChangingAnEntryLeavesTodaysTask(): void
    {
        $entry = $this->withHandlers(fn () => $this->create($this->everyDay()));

        $this->handle($this->eventRaisedBy(TaskPlanningEntryUpdatedEvent::class, fn () => $this->update($entry, $this->everyDay('Arroser le jardin'))));

        $tasks = $this->plannedTasks();
        self::assertCount(1, $tasks);
        self::assertSame('Arroser les plantes', $tasks[0]->title);
    }

    /** Deleted, its tasks to come go; today's stays, unhooked. */
    public function testDeletingAnEntryDeletesItsTasksToCome(): void
    {
        $everyDay = $this->withHandlers(fn () => $this->create($this->everyDay()));
        $once = $this->withHandlers(fn () => $this->create($this->singleDate($this->inDays(10))));

        $this->handle($this->eventRaisedBy(TaskPlanningEntryDeletedEvent::class, fn () => $this->delete($once)));
        $this->handle($this->eventRaisedBy(TaskPlanningEntryDeletedEvent::class, fn () => $this->delete($everyDay)));

        $tasks = $this->plannedTasks();
        self::assertSame([$this->today()], $this->plannedDates());
        self::assertNull($tasks[0]->planningEntry);
    }

    public function testMakingTheTasksStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(TaskPlanningEntryCreatedEvent::class, fn () => $this->create($this->everyDay())),
            fn (EventInterface $event) => $this->handle($event),
            // The dates already made, then today's INSERT.
            max: 2,
        );
    }

    public function testRemakingTheTasksStaysWithinItsQueryBudget(): void
    {
        $entry = $this->withHandlers(fn () => $this->create($this->singleDate($this->inDays(10))));

        $this->assertQueryCountAtMost(
            fn () => $this->eventRaisedBy(TaskPlanningEntryUpdatedEvent::class, fn () => $this->update($entry, $this->singleDate($this->inDays(12)))),
            fn (EventInterface $event) => $this->handle($event),
            // Its open tasks, the DELETE of the one ahead, the dates already made, the INSERT.
            max: 4,
        );
    }

    private function handler(): SyncPlannedTasksEventHandler
    {
        return self::getContainer()->get(SyncPlannedTasksEventHandler::class);
    }

    private function create(SaveTaskPlanningEntryDataInput $input): TaskPlanningEntryDataOutput
    {
        return $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $input);
    }

    private function update(TaskPlanningEntryDataOutput $entry, SaveTaskPlanningEntryDataInput $input): void
    {
        $this->useCase(UpdateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $entry->id, $input);
    }

    private function delete(TaskPlanningEntryDataOutput $entry): void
    {
        $this->useCase(DeleteTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $entry->id);
    }

    /** @return list<string> */
    private function plannedDates(): array
    {
        return array_map(static fn ($task) => (string) $task->plannedDate?->format('Y-m-d'), $this->plannedTasks());
    }
}
