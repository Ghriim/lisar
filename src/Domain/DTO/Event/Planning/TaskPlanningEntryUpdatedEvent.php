<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A task entry was replaced whole: its model, its schedule, or both. */
final readonly class TaskPlanningEntryUpdatedEvent implements EventInterface
{
    public function __construct(
        public TaskPlanningEntryDataModel $entry,
    ) {
    }
}
