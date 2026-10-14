<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\UserRoleRegistry;
use LogicException;

/**
 * The caller an integration test hands a use case, as the controller would: the account loaded
 * from the database, managed, so a use case can make it the owner of what it writes.
 */
trait SignedInUserTrait
{
    private function currentUser(int $id): UserDataModel
    {
        return self::getContainer()->get(UserProviderGateway::class)->findOneById($id)
            ?? throw new LogicException(sprintf('No seeded account %d.', $id));
    }

    /** An administrator for the back-office use cases, which only check the role: never stored. */
    private function currentAdmin(): UserDataModel
    {
        $admin = new UserDataModel();
        $admin->role = UserRoleRegistry::ADMIN;

        return $admin;
    }

    /** A signed-in account for the use cases that only read the catalogue: never stored. */
    private function anyCurrentUser(): UserDataModel
    {
        return new UserDataModel();
    }
}
