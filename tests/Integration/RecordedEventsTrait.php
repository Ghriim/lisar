<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\DTO\Event\EventInterface;
use App\Tests\Integration\Event\RecordingEventDispatcher;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function implode;
use function sprintf;

/**
 * What a use case raised. Its handlers do not run in a use case test: the test asserts the event
 * and what it carries, and the handler's own test asserts what it does with it.
 */
trait RecordedEventsTrait
{
    /**
     * The one event of that class raised since the fixtures were loaded, handed back for the test
     * to check what it carries.
     *
     * @template T of EventInterface
     *
     * @param class-string<T> $eventClass
     *
     * @return T
     */
    private function assertEventRaised(string $eventClass): EventInterface
    {
        $raised = $this->eventsRaised($eventClass);

        self::assertCount(1, $raised, sprintf('%s raised %d times; raised: %s.', $eventClass, count($raised), $this->describeRaised()));

        return $raised[0];
    }

    /**
     * @param class-string<EventInterface>|null $eventClass none of that class, or none at all
     */
    private function assertNoEventRaised(?string $eventClass = null): void
    {
        $raised = null === $eventClass ? $this->recordingEventDispatcher()->raised : $this->eventsRaised($eventClass);

        self::assertSame([], $raised, sprintf('Expected no %s; raised: %s.', $eventClass ?? 'event', $this->describeRaised()));
    }

    /** From here on: what an arrangement raised is not what the call under test raises. */
    private function forgetRaisedEvents(): void
    {
        $this->recordingEventDispatcher()->forget();
    }

    /**
     * Runs `$operation` with the handlers on. For an arrangement that needs what they write; never
     * for the call a test is about.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    private function withHandlers(callable $operation): mixed
    {
        $result = $this->recordingEventDispatcher()->withHandlers($operation);
        $this->recordingEventDispatcher()->forget();

        return $result;
    }

    /**
     * @template T of EventInterface
     *
     * @param class-string<T> $eventClass
     *
     * @return list<T>
     */
    private function eventsRaised(string $eventClass): array
    {
        return array_values(array_filter(
            $this->recordingEventDispatcher()->raised,
            static fn (EventInterface $event): bool => $event instanceof $eventClass,
        ));
    }

    private function describeRaised(): string
    {
        $raised = $this->recordingEventDispatcher()->raised;

        return [] === $raised ? 'none' : implode(', ', array_map(static fn (EventInterface $event): string => $event::class, $raised));
    }

    private function recordingEventDispatcher(): RecordingEventDispatcher
    {
        return self::getContainer()->get(RecordingEventDispatcher::class);
    }
}
