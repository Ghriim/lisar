<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryCreatedEvent;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryDeletedEvent;
use App\Domain\DTO\Event\Planning\TaskPlanningEntryUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Planning\TaskPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

use function spl_object_id;

/**
 * Every write of a task entry says so: the tasks it makes follow it (SyncPlannedTasksEventHandler).
 *
 * @extends AbstractBaseMysqlPersister<TaskPlanningEntryDataModel>
 */
final class TaskPlanningEntryPersister extends AbstractBaseMysqlPersister implements TaskPlanningEntryPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TaskProviderGateway $taskProviderGateway,
    ) {
        parent::__construct($entityManager);
    }

    public function create(TaskPlanningEntryDataModel $entry): TaskPlanningEntryDataModel
    {
        $this->inTransaction(function () use ($entry): void {
            $this->persistCreate($entry);
            $this->eventDispatcher->dispatch(new TaskPlanningEntryCreatedEvent($entry));
        });

        return $entry;
    }

    public function update(TaskPlanningEntryDataModel $entry): TaskPlanningEntryDataModel
    {
        $this->inTransaction(function () use ($entry): void {
            $this->persistUpdate($entry);
            $this->eventDispatcher->dispatch(new TaskPlanningEntryUpdatedEvent($entry));
        });

        return $entry;
    }

    public function delete(TaskPlanningEntryDataModel $entry): void
    {
        // Read before the delete: the database unhooks the tasks from the entry as it goes.
        $openTasks = $this->taskProviderGateway->findOpenForPlanningEntry($entry);

        $this->inTransaction(function () use ($entry, $openTasks): void {
            $this->persistDelete($entry);
            $this->unhookLoadedTasks($entry);
            $this->eventDispatcher->dispatch(new TaskPlanningEntryDeletedEvent($openTasks));
        });
    }

    public function deleteAllForTodo(TodoDataModel $todo): void
    {
        /** @var list<TaskPlanningEntryDataModel> $loadedEntries */
        $loadedEntries = array_values(array_filter(
            $this->entityManager->getUnitOfWork()->getIdentityMap()[TaskPlanningEntryDataModel::class] ?? [],
            static fn (object $loaded): bool => $loaded instanceof TaskPlanningEntryDataModel && $loaded->todo === $todo,
        ));

        $this->entityManager->createQueryBuilder()
            ->delete(TaskPlanningEntryDataModel::class, 'entry')
            ->andWhere('entry.todo = :todo')
            ->setParameter('todo', $todo)
            ->getQuery()
            ->execute();

        // What the request had loaded of them is gone from the table: left in the identity map, a
        // later flush would write them back, and the tasks pointing at them would follow.
        foreach ($loadedEntries as $entry) {
            $this->unhookLoadedTasks($entry);
            $this->entityManager->detach($entry);
        }
    }

    /**
     * What the database did through ON DELETE SET NULL, done to the tasks the request had loaded:
     * left pointing at an entry that is gone, they would make the next flush take it for a new
     * row. Their original data follows, so that no flush writes the null again.
     */
    private function unhookLoadedTasks(TaskPlanningEntryDataModel $entry): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap()[TaskDataModel::class] ?? [] as $loaded) {
            if ($loaded instanceof TaskDataModel && $loaded->planningEntry === $entry) {
                $loaded->planningEntry = null;
                $unitOfWork->setOriginalEntityProperty(spl_object_id($loaded), 'planningEntry', null);
            }
        }
    }
}
