<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Todo\TodoDataModelFactory;
use App\Domain\Factory\OutputFactory\Todo\TodoOutputFactory;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Validation\Validator\Todo\CreateTodoValidator;
use App\UseCase\UseCaseInterface;

final readonly class CreateTodoUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateTodoValidator $validator,
        private TodoProviderGateway $todoProviderGateway,
        private TodoPersisterGateway $todoPersisterGateway,
        private TodoDataModelFactory $todoFactory,
        private TodoOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, SaveTodoDataInput $input): TodoDataOutput
    {
        $this->validator->validate(
            $currentUser,
            $input,
            $this->todoProviderGateway->findOneByTitleForOwner(trim($input->title), $currentUser),
        );

        $todo = $this->todoFactory->build($currentUser, $input->title);
        $this->todoPersisterGateway->create($todo);

        return $this->outputFactory->buildOne($todo);
    }
}
