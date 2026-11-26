<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Planning;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Todo\TodoUpdatedEvent;
use App\Domain\Gateway\Persister\Planning\TaskPlanningEntryPersisterGateway;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Archiving a TODO takes its planning with it: the entries that make its tasks go, and made
 * active again it plans nothing until new entries are made. The tasks already made stay, in the
 * TODO, unhooked from their entry.
 */
final readonly class DeleteTodoPlanningEntriesEventHandler implements EventHandlerInterface
{
    public function __construct(
        // Lazy: this persister raises events, and the dispatcher builds every handler as it is
        // built itself — eager, it would wait on the dispatcher it is part of.
        #[Autowire(lazy: true)]
        private TaskPlanningEntryPersisterGateway $taskPlanningEntryPersisterGateway,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [TodoUpdatedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        if (false === $event instanceof TodoUpdatedEvent) {
            throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class));
        }

        if (true === $event->archives) {
            $this->taskPlanningEntryPersisterGateway->deleteAllForTodo($event->todo);
        }
    }
}
