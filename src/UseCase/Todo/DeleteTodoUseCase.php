<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Validator\Todo\DeleteTodoValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a TODO, archived or not, and with it — by the database's cascade — its tasks and the
 * planning entries making them. The front asks first, and says the tasks go too.
 */
final readonly class DeleteTodoUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteTodoValidator $validator,
        private TodoProviderGateway $todoProviderGateway,
        private TodoPersisterGateway $todoPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $todoId): void
    {
        $todo = $this->todoProviderGateway->findOneById($todoId);
        if (null === $todo) {
            throw new DataModelNotFoundException(TodoDataModel::class);
        }

        $this->validator->validate($currentUser, $todo);

        $this->todoPersisterGateway->delete($todo);
    }
}
