<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A task model was planned. */
final readonly class TaskPlanningEntryCreatedEvent implements EventInterface
{
    public function __construct(
        public TaskPlanningEntryDataModel $entry,
    ) {
    }
}
