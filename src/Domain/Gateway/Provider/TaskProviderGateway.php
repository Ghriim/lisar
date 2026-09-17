<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

interface TaskProviderGateway
{
    /** Scoped to the owner: a task belonging to someone else is simply not found. */
    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?TaskDataModel;

    /**
     * The account's own list: root tasks only, subtasks nested underneath, sorted by category
     * then by priority weight.
     *
     * @return list<TaskDataModel>
     */
    public function findAllForOwnerList(UserDataModel $owner, bool $isDone): array;

    /** Whether a priority may still be deleted. */
    public function countForPriority(PriorityDataModel $priority): int;

    /** Whether a category may still be deleted. */
    public function countForCategory(CategoryDataModel $category): int;
}
