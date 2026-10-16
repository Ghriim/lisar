<?php

declare(strict_types=1);

namespace App\Tests\Integration\Event;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\Event\EventDispatcherInterface;

/**
 * Stands in for the dispatcher in the test environment: it keeps every event raised, and runs
 * the handlers only while it is told to. A use case test asserts what was raised, not what the
 * handlers did with it; each handler has its own test (backend-architecture.md §9.3).
 *
 * The fixtures do run the handlers (LoadFixturesTrait): they write through the persisters, and a
 * seeded workout without its summary or its records would be a state no request ever leaves.
 */
final class RecordingEventDispatcher implements EventDispatcherInterface
{
    /** @var list<EventInterface> */
    public private(set) array $raised = [];

    public private(set) bool $runsHandlers = false;

    public function __construct(private readonly EventDispatcherInterface $inner)
    {
    }

    public function dispatch(EventInterface $event): void
    {
        $this->raised[] = $event;

        if (true === $this->runsHandlers) {
            $this->inner->dispatch($event);
        }
    }

    /**
     * Runs `$operation` with the handlers on, as in production.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function withHandlers(callable $operation): mixed
    {
        $previous = $this->runsHandlers;
        $this->runsHandlers = true;

        try {
            return $operation();
        } finally {
            $this->runsHandlers = $previous;
        }
    }

    public function forget(): void
    {
        $this->raised = [];
    }
}
