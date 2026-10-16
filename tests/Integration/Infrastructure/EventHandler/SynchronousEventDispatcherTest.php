<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EventHandler;

use App\Domain\DTO\Event\Tracking\HydrationEntryCreatedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryDeletedEvent;
use App\Domain\DTO\Event\Tracking\HydrationEntryUpdatedEvent;
use App\Domain\DTO\Event\Tracking\StepDayCreatedEvent;
use App\Domain\DTO\Event\Tracking\StepDayUpdatedEvent;
use App\Domain\DTO\Event\Training\WorkoutBlockDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutExerciseDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetCreatedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetDeletedEvent;
use App\Domain\DTO\Event\Training\WorkoutSetUpdatedEvent;
use App\Domain\DTO\Event\Training\WorkoutUpdatedEvent;
use App\Infrastructure\EventHandler\Habits\SyncHydrationHabitsEventHandler;
use App\Infrastructure\EventHandler\Habits\SyncStepHabitsEventHandler;
use App\Infrastructure\EventHandler\Habits\SyncWorkoutHabitsEventHandler;
use App\Infrastructure\EventHandler\SynchronousEventDispatcher;
use App\Infrastructure\EventHandler\Training\RefreshPersonalBestsEventHandler;
use App\Infrastructure\EventHandler\Training\RefreshWorkoutSummaryEventHandler;
use App\Tests\Integration\Event\RecordingEventDispatcher;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The wiring the use case tests no longer cross: which handlers the container hands each event to.
 * The table of domain-events.md, checked against the container.
 */
final class SynchronousEventDispatcherTest extends KernelTestCase
{
    public function testEachEventReachesItsHandlers(): void
    {
        $dispatcher = self::getContainer()->get(RecordingEventDispatcher::class.'.inner');
        self::assertInstanceOf(SynchronousEventDispatcher::class, $dispatcher);

        $handlersByEvent = [];
        foreach ((new ReflectionProperty(SynchronousEventDispatcher::class, 'handlersByEvent'))->getValue($dispatcher) as $eventClass => $handlers) {
            $handlerClasses = array_map(static fn (object $handler): string => $handler::class, $handlers);
            sort($handlerClasses);
            $handlersByEvent[$eventClass] = $handlerClasses;
        }
        ksort($handlersByEvent);

        $records = RefreshPersonalBestsEventHandler::class;
        $summary = RefreshWorkoutSummaryEventHandler::class;
        $expected = [
            HydrationEntryCreatedEvent::class => [SyncHydrationHabitsEventHandler::class],
            HydrationEntryDeletedEvent::class => [SyncHydrationHabitsEventHandler::class],
            HydrationEntryUpdatedEvent::class => [SyncHydrationHabitsEventHandler::class],
            StepDayCreatedEvent::class => [SyncStepHabitsEventHandler::class],
            StepDayUpdatedEvent::class => [SyncStepHabitsEventHandler::class],
            WorkoutBlockDeletedEvent::class => [$records, $summary],
            WorkoutCreatedEvent::class => [$summary],
            WorkoutDeletedEvent::class => [SyncWorkoutHabitsEventHandler::class, $records],
            WorkoutExerciseDeletedEvent::class => [$records, $summary],
            WorkoutSetCreatedEvent::class => [$records, $summary],
            WorkoutSetDeletedEvent::class => [$records, $summary],
            WorkoutSetUpdatedEvent::class => [$records, $summary],
            WorkoutUpdatedEvent::class => [SyncWorkoutHabitsEventHandler::class, $records],
        ];
        foreach ($expected as &$handlerClasses) {
            sort($handlerClasses);
        }
        ksort($expected);

        self::assertSame($expected, $handlersByEvent);
    }
}
