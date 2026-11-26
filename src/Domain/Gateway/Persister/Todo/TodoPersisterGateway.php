<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;

interface TodoPersisterGateway
{
    public function create(TodoDataModel $todo): TodoDataModel;

    /** Raises TodoUpdatedEvent: archiving a TODO takes its planning with it. */
    public function update(TodoDataModel $todo): TodoDataModel;

    /** Deletes the TODO and, in cascade, its tasks and its planning entries. */
    public function delete(TodoDataModel $todo): void;
}
