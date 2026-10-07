<?php

declare(strict_types=1);

namespace App\UseCase\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\User\Admin\UserCommentDataOutput;
use App\Domain\Factory\OutputFactory\User\UserCommentOutputFactory;
use App\Domain\Gateway\Provider\User\UserCommentProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class ListUserCommentsUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private UserCommentProviderGateway $userCommentProviderGateway,
        private UserCommentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<UserCommentDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(int $userId): array
    {
        $user = $this->userProviderGateway->findOneById($userId);

        if (null === $user) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        return $this->outputFactory->buildMany($this->userCommentProviderGateway->findAllForUser($user));
    }
}
