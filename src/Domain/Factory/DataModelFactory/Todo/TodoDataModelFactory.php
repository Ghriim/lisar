<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Registry\Todo\TodoStatusRegistry;

final readonly class TodoDataModelFactory
{
    /** What every account's default TODO is called when it is made. The person may rename it. */
    public const string DEFAULT_TITLE = 'A faire';

    /** The TODO every account is made with: its default, active, never archived nor deleted. */
    public function buildDefault(UserDataModel $owner): TodoDataModel
    {
        $todo = $this->build($owner, self::DEFAULT_TITLE);
        $todo->isDefault = true;

        return $todo;
    }

    /** A TODO of the account's own, active. The title comes trimmed: stored as the uniqueness compares it. */
    public function build(UserDataModel $owner, string $title): TodoDataModel
    {
        $todo = new TodoDataModel();
        $todo->owner = $owner;
        $todo->title = trim($title);
        $todo->status = TodoStatusRegistry::ACTIVE;

        return $todo;
    }
}
