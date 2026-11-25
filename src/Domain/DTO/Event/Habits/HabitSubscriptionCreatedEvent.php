<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Habits;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A habit was taken on for the first time. */
final readonly class HabitSubscriptionCreatedEvent implements EventInterface
{
    public function __construct(
        public HabitSubscriptionDataModel $subscription,
    ) {
    }
}
