<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Factory\OutputFactory\UserOutputFactory;
use App\Domain\Gateway\Persister\UserPersisterGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Reactivating an account. The person signs in again as before; the sessions dropped by the
 * deactivation are not resurrected.
 */
final readonly class ActivateUserUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private UserPersisterGateway $userPersisterGateway,
        private UserOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): UserDataOutput
    {
        $user = $this->userProviderGateway->findOneById($id);

        if (null === $user) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $user->isActive = true;
        $this->userPersisterGateway->update($user);

        return $this->outputFactory->buildOne($user);
    }
}
