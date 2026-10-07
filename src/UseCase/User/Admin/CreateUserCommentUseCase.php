<?php

declare(strict_types=1);

namespace App\UseCase\User\Admin;

use App\Domain\DTO\DataModel\User\UserCommentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\User\Admin\CreateUserCommentDataInput;
use App\Domain\DTO\Output\User\Admin\UserCommentDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\User\UserCommentOutputFactory;
use App\Domain\Gateway\Persister\User\UserCommentPersisterGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Validation\Validator\User\Admin\CreateUserCommentValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class CreateUserCommentUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateUserCommentValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private UserCommentPersisterGateway $userCommentPersisterGateway,
        private UserCommentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @param int $authorId the administrator writing the note, taken from their access token
     *                      and never from the payload
     *
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $userId, int $authorId, CreateUserCommentDataInput $input): UserCommentDataOutput
    {
        $user = $this->userProviderGateway->findOneById($userId);
        $author = $this->userProviderGateway->findOneById($authorId);

        if (null === $user || null === $author) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate($input);

        $comment = new UserCommentDataModel();
        $comment->user = $user;
        $comment->author = $author;
        $comment->body = $input->body;

        $this->userCommentPersisterGateway->create($comment);

        return $this->outputFactory->buildOne($comment);
    }
}
