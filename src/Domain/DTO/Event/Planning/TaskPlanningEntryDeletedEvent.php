<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Planning;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A task entry was deleted. */
final readonly class TaskPlanningEntryDeletedEvent implements EventInterface
{
    /**
     * @param list<TaskDataModel> $openTasks the tasks it had made and were not done, read before
     *                                       the delete unhooked them from it
     */
    public function __construct(
        public array $openTasks,
    ) {
    }
}
