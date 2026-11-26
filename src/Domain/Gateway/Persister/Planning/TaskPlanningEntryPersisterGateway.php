<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;

interface TaskPlanningEntryPersisterGateway
{
    public function create(TaskPlanningEntryDataModel $entry): TaskPlanningEntryDataModel;

    public function update(TaskPlanningEntryDataModel $entry): TaskPlanningEntryDataModel;

    /** Deletes the entry; the tasks it made stay, unhooked from it (ON DELETE SET NULL). */
    public function delete(TaskPlanningEntryDataModel $entry): void;

    /**
     * Deletes every entry making tasks in that TODO, raising nothing: what calls it is a handler.
     * The tasks they made stay, unhooked from them (ON DELETE SET NULL).
     */
    public function deleteAllForTodo(TodoDataModel $todo): void;
}
