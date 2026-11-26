<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Todo;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\User\UserCreatedEvent;
use App\Domain\Factory\DataModelFactory\Todo\TodoDataModelFactory;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Every account starts with its « A faire »: the TODO a task goes in when none is named.
 */
final readonly class CreateDefaultTodoEventHandler implements EventHandlerInterface
{
    public function __construct(
        private TodoDataModelFactory $todoFactory,
        // Lazy: this persister raises events, and the dispatcher builds every handler as it is
        // built itself — eager, it would wait on the dispatcher it is part of.
        #[Autowire(lazy: true)]
        private TodoPersisterGateway $todoPersisterGateway,
    ) {
    }

    public static function getSupportedEvents(): array
    {
        return [UserCreatedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        if (false === $event instanceof UserCreatedEvent) {
            throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class));
        }

        $this->todoPersisterGateway->create($this->todoFactory->buildDefault($event->user));
    }
}
