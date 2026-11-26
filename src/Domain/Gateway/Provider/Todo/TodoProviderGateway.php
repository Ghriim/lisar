<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface TodoProviderGateway
{
    /** Whoever it belongs to: whether the caller may touch it is the validator's to say. */
    public function findOneById(int $id): ?TodoDataModel;

    /** The account's « A faire », the one made with it. */
    public function findDefaultForOwner(UserDataModel $owner): ?TodoDataModel;

    /**
     * The account's TODOs in that status — all of them when null — the default first, then by
     * title.
     *
     * @return list<TodoDataModel>
     */
    public function findAllForOwner(UserDataModel $owner, ?string $status): array;

    /** Used to keep titles unique: the column's collation makes the comparison ignore case. */
    public function findOneByTitleForOwner(string $title, UserDataModel $owner): ?TodoDataModel;
}
