<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\TaskDataModel;

interface TaskPersisterGateway
{
    public function create(TaskDataModel $task): TaskDataModel;

    public function update(TaskDataModel $task): TaskDataModel;

    /** Deletes the task and, in cascade, its subtasks. */
    public function delete(TaskDataModel $task): void;
}
