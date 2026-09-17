<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\UserIdentityDataModel;
use App\Domain\Gateway\Persister\UserIdentityPersisterGateway;
use App\Domain\Gateway\Persister\UserPersisterGateway;
use App\Domain\Registry\User\IdentityProviderRegistry;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\User\PasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    public const string ALICE = 'user-alice';
    public const string BOB_DEACTIVATED = 'user-bob-deactivated';
    public const string ADMIN = 'user-admin';

    /** The password every seeded account signs in with. */
    public const string PLAIN_PASSWORD = 'Corr3ct-Horse!';

    public function __construct(
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly UserPersisterGateway $userPersisterGateway,
        private readonly UserIdentityPersisterGateway $userIdentityPersisterGateway,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->addReference(
            self::ALICE,
            $this->createAccount('alice', 'alice@lisar.test', UserRoleRegistry::USER, true),
        );
        $this->addReference(
            self::BOB_DEACTIVATED,
            $this->createAccount('bob', 'bob@lisar.test', UserRoleRegistry::USER, false),
        );
        $this->addReference(
            self::ADMIN,
            $this->createAccount('admin', 'admin@lisar.test', UserRoleRegistry::ADMIN, true),
        );
    }

    /**
     * Written through the persisters, in the same order as the sign-up use case: the account
     * first, its identity second. Seeded rows then carry the timestamps a real sign-up produces.
     */
    private function createAccount(string $username, string $email, string $role, bool $isActive): UserDataModel
    {
        $user = new UserDataModel();
        $user->username = $username;
        $user->email = $email;
        $user->role = $role;
        $user->isActive = $isActive;

        $this->userPersisterGateway->create($user);

        $identity = new UserIdentityDataModel();
        $identity->user = $user;
        $identity->provider = IdentityProviderRegistry::PASSWORD;
        $identity->passwordHash = $this->passwordHasher->hash(self::PLAIN_PASSWORD);

        $this->userIdentityPersisterGateway->create($identity);

        $user->identities->add($identity);

        return $user;
    }
}
