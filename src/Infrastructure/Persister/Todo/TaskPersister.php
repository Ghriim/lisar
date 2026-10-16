<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;

/**
 * @extends AbstractBaseMysqlPersister<TaskDataModel>
 */
final class TaskPersister extends AbstractBaseMysqlPersister implements TaskPersisterGateway
{
    public function create(TaskDataModel $task): TaskDataModel
    {
        return $this->persistCreate($task);
    }

    public function update(TaskDataModel $task): TaskDataModel
    {
        return $this->persistUpdate($task);
    }

    public function delete(TaskDataModel $task): void
    {
        $this->persistDelete($task);
    }
}
