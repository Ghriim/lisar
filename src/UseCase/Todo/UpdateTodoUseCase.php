<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TodoOutputFactory;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Validator\Todo\UpdateTodoValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Renaming a TODO — the default included. */
final readonly class UpdateTodoUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateTodoValidator $validator,
        private TodoProviderGateway $todoProviderGateway,
        private TodoPersisterGateway $todoPersisterGateway,
        private TodoOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $todoId, SaveTodoDataInput $input): TodoDataOutput
    {
        $todo = $this->todoProviderGateway->findOneById($todoId);
        if (null === $todo) {
            throw new DataModelNotFoundException(TodoDataModel::class);
        }

        $title = trim($input->title);
        $this->validator->validate($currentUser, $todo, $input, $this->todoProviderGateway->findOneByTitleForOwner($title, $currentUser));

        $todo->title = $title;
        $this->todoPersisterGateway->update($todo);

        return $this->outputFactory->buildOne($todo);
    }
}
