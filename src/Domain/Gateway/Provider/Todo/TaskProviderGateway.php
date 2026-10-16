<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface TaskProviderGateway
{
    /** Whoever it belongs to: whether the caller may touch it is the validator's to say. */
    public function findOneById(int $id): ?TaskDataModel;

    /**
     * The account's own list: root tasks only, subtasks nested underneath, sorted by category
     * then by priority, most pressing first.
     *
     * @return list<TaskDataModel>
     */
    public function findAllForOwnerList(UserDataModel $owner, bool $isDone): array;

    /** Whether a category may still be deleted. */
    public function countForCategory(CategoryDataModel $category): int;
}
