<?php

declare(strict_types=1);

namespace App\UseCase\User;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\User\UserDataOutput;
use App\Domain\Factory\OutputFactory\UserOutputFactory;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class GetUserUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
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

        return $this->outputFactory->buildOne($user);
    }
}
