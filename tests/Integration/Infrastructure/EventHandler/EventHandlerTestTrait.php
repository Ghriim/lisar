<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler;

use App\Domain\DTO\Event\EventInterface;
use App\Infrastructure\EventHandler\EventHandlerInterface;
use App\Tests\Integration\RecordedEventsTrait;

/**
 * What a handler's test does: lay the state out as production leaves it — handlers on — make the
 * write with them off, and hand the event it raised to the one handler under test, alone.
 *
 *     $workout = $this->withHandlers(fn () => $this->addSet($this->benchWorkout(), $input));
 *     $this->handle($this->eventRaisedBy(WorkoutSetUpdatedEvent::class, fn () => $this->complete($workout, $set)));
 *
 * The event is the one the persister really raised, with the data models the request holds; the
 * other handlers of that event have their own tests.
 */
trait EventHandlerTestTrait
{
    use RecordedEventsTrait;

    /** The handler under test, from the container. */
    abstract private function handler(): EventHandlerInterface;

    private function handle(EventInterface $event): void
    {
        $this->handler()->handle($event);
    }

    /**
     * Runs the write, handlers off, and hands back the one event of that class it raised.
     *
     * @template T of EventInterface
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    private function eventRaisedBy(string $eventClass, callable $write): EventInterface
    {
        $this->forgetRaisedEvents();
        $write();

        return $this->assertEventRaised($eventClass);
    }
}
