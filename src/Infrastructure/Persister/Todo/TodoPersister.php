<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\Event\Todo\TodoUpdatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creating a TODO raises nothing — the default one is made by a handler, whose writes must raise
 * nothing. Changing one says so, and whether it archives it.
 *
 * @extends AbstractBaseMysqlPersister<TodoDataModel>
 */
final class TodoPersister extends AbstractBaseMysqlPersister implements TodoPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(TodoDataModel $todo): TodoDataModel
    {
        return $this->persistCreate($todo);
    }

    public function update(TodoDataModel $todo): TodoDataModel
    {
        // Read before the flush forgets it: only the write that puts it away archives it.
        $archives = TodoStatusRegistry::ARCHIVED !== $this->originalValueOf($todo, 'status')
            && TodoStatusRegistry::ARCHIVED === $todo->status;

        $this->inTransaction(function () use ($todo, $archives): void {
            $this->persistUpdate($todo);
            $this->eventDispatcher->dispatch(new TodoUpdatedEvent($todo, $archives));
        });

        return $todo;
    }

    public function delete(TodoDataModel $todo): void
    {
        $this->persistDelete($todo);
    }
}
