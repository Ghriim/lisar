<?php

declare(strict_types=1);

namespace App\Fixtures\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Factory\DataModelFactory\Todo\TodoDataModelFactory;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Every account's « A faire » — the one the account's creation made (CreateDefaultTodoEventHandler),
 * or one made here should it be missing — and, for Alice, « Maison », active, and « Vieux
 * projets », archived.
 */
final class TodoFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_DEFAULT = 'todo-alice-default';
    public const string ALICE_HOME = 'todo-alice-home';
    public const string ALICE_ARCHIVED = 'todo-alice-archived';
    public const string BOB_DEFAULT = 'todo-bob-default';
    public const string ADMIN_DEFAULT = 'todo-admin-default';

    public function __construct(
        private readonly TodoDataModelFactory $todoFactory,
        private readonly TodoProviderGateway $todoProviderGateway,
        private readonly TodoPersisterGateway $todoPersisterGateway,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);

        $this->addReference(self::ALICE_DEFAULT, $this->defaultOf($alice));
        $this->addReference(self::BOB_DEFAULT, $this->defaultOf($this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class)));
        $this->addReference(self::ADMIN_DEFAULT, $this->defaultOf($this->getReference(UserFixtures::ADMIN, UserDataModel::class)));

        $home = $this->todoFactory->build($alice, 'Maison');
        $this->todoPersisterGateway->create($home);
        $this->addReference(self::ALICE_HOME, $home);

        $archived = $this->todoFactory->build($alice, 'Vieux projets');
        $archived->status = TodoStatusRegistry::ARCHIVED;
        $this->todoPersisterGateway->create($archived);
        $this->addReference(self::ALICE_ARCHIVED, $archived);
    }

    /** The account's « A faire »: the one its creation made, or else one made now. */
    private function defaultOf(UserDataModel $user): TodoDataModel
    {
        $default = $this->todoProviderGateway->findDefaultForOwner($user);
        if (null !== $default) {
            return $default;
        }

        return $this->todoPersisterGateway->create($this->todoFactory->buildDefault($user));
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
