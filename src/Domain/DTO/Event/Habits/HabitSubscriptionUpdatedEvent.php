<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Habits;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A habit was dropped — or taken back up. */
final readonly class HabitSubscriptionUpdatedEvent implements EventInterface
{
    public function __construct(
        public HabitSubscriptionDataModel $subscription,
        /** Whether this write is the one dropping it: the only change its planning follows. */
        public bool $deactivates,
    ) {
    }
}
