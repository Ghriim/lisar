<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\UserDataModel;

interface UserProviderGateway
{
    public function findOneById(int $id): ?UserDataModel;

    public function findOneByEmail(string $email): ?UserDataModel;

    public function findOneByUsername(string $username): ?UserDataModel;

    /**
     * The back-office list: free-text search over the username and the e-mail, optional status
     * filter, newest accounts first.
     *
     * @return list<UserDataModel>
     */
    public function findAllForAdminList(?string $search, ?bool $isActive, int $limit, int $offset): array;

    /** How many accounts the same filters match, ignoring pagination. */
    public function countAllForAdminList(?string $search, ?bool $isActive): int;
}
