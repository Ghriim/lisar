<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\Event\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Runs the handlers of an event in the request that raised it. Several handlers may listen to one
 * event: they run in the order the container lists them, and none of them may count on another.
 */
final readonly class SynchronousEventDispatcher implements EventDispatcherInterface
{
    /** @var array<class-string<EventInterface>, list<EventHandlerInterface>> */
    private array $handlersByEvent;

    /** @param iterable<EventHandlerInterface> $handlers */
    public function __construct(
        #[AutowireIterator(EventHandlerInterface::TAG)] iterable $handlers,
    ) {
        $handlersByEvent = [];
        foreach ($handlers as $handler) {
            foreach ($handler::getSupportedEvents() as $eventClass) {
                $handlersByEvent[$eventClass][] = $handler;
            }
        }

        $this->handlersByEvent = $handlersByEvent;
    }

    public function dispatch(EventInterface $event): void
    {
        foreach ($this->handlersByEvent[$event::class] ?? [] as $handler) {
            $handler->handle($event);
        }
    }
}
