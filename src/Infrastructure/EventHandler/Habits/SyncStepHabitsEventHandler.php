<?php

declare(strict_types=1);

namespace App\Infrastructure\EventHandler\Habits;

use App\Domain\DTO\Event\EventInterface;
use App\Domain\DTO\Event\Tracking\StepDayCreatedEvent;
use App\Domain\DTO\Event\Tracking\StepDayUpdatedEvent;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use LogicException;

/** A day's step count noted moves the step habits of that day. */
final readonly class SyncStepHabitsEventHandler extends AbstractTrackerHabitsEventHandler
{
    public static function getSupportedEvents(): array
    {
        return [StepDayCreatedEvent::class, StepDayUpdatedEvent::class];
    }

    public function handle(EventInterface $event): void
    {
        if (false === $event instanceof StepDayCreatedEvent && false === $event instanceof StepDayUpdatedEvent) {
            throw new LogicException(sprintf('%s does not handle %s.', self::class, $event::class));
        }

        $this->keep($event->stepDay->owner, HabitTrackerRegistry::STEPS, $event->stepDay->countInSteps, $event->stepDay->day);
    }
}
