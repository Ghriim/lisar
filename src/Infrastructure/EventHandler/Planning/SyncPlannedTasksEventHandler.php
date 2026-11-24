<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryCreatedEvent;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryDeletedEvent;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryUpdatedEvent;
use App\Domain\Factory\DataModelFactory\Todo\TaskDataModelFactory;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Planning\TaskPlanningCalendar;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;

use function in_array;

/**
 * The tasks a task entry makes follow it. Created, it makes at once what is due now — a single
 * date's task, today's for a recurring rule. Changed, its tasks to come — made ahead, not done —
 * go and are made again from the new model and rule; today's task and those before stay. Deleted,
 * its tasks to come go; the others stay, unhooked from it.
 */
final readonly class SyncPlannedTasksEventHandler implements EventHandlerInterface
{
    public function __construct(
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskPlanningCalendar $calendar,
        private TaskDataModelFactory $taskFactory,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [TaskPlanningEntryCreatedEvent::class, TaskPlanningEntryUpdatedEvent::class, TaskPlanningEntryDeletedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        match (true) {
            $event instanceof TaskPlanningEntryCreatedEvent => $this->makeDueTasks($event->entry),
            $event instanceof TaskPlanningEntryUpdatedEvent => $this->remake($event->entry),
            $event instanceof TaskPlanningEntryDeletedEvent => $this->deleteToCome($event->openTasks),
            default => throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class)),
        };
    }

    private function remake(TaskPlanningEntryDataModel $entry): void
    {
        $this->deleteToCome($this->taskProviderGateway->findOpenForPlanningEntry($entry));
        $this->makeDueTasks($entry);
    }

    private function makeDueTasks(TaskPlanningEntryDataModel $entry): void
    {
        $days = $this->calendar->datesToMakeNow($entry);
        $alreadyMade = $this->taskProviderGateway->findPlannedDatesForPlanningEntry($entry, $days);

        foreach ($days as $day) {
            if (false === in_array($day->format('Y-m-d'), $alreadyMade, true)) {
                $this->taskPersisterGateway->create($this->taskFactory->buildPlanned($entry, $day));
            }
        }
    }

    /** @param list<TaskDataModel> $tasks */
    private function deleteToCome(array $tasks): void
    {
        foreach ($tasks as $task) {
            if (true === $this->calendar->isToCome($task)) {
                $this->taskPersisterGateway->delete($task);
            }
        }
    }
}
