<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\User\UserCreatedEvent;
use App\Domain\Event\EventDispatcherInterface;
use App\Domain\Gateway\Persister\User\UserPersisterGateway;
use App\Infrastructure\Persister\AbstractBaseMysqlPersister;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creating an account says so: what every account starts with — its « A faire » — follows from it
 * (CreateDefaultTodoEventHandler).
 *
 * @extends AbstractBaseMysqlPersister<UserDataModel>
 */
final class UserPersister extends AbstractBaseMysqlPersister implements UserPersisterGateway
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($entityManager);
    }

    public function create(UserDataModel $user): UserDataModel
    {
        $this->inTransaction(function () use ($user): void {
            $this->persistCreate($user);
            $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
        });

        return $user;
    }

    public function update(UserDataModel $user): UserDataModel
    {
        return $this->persistUpdate($user);
    }
}
