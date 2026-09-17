<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Admin\UserCommentDataOutput;
use App\Domain\Factory\OutputFactory\UserCommentOutputFactory;
use App\Domain\Gateway\Provider\UserCommentProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
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
