<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\TaskDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TaskOutputFactory;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\GetTaskValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class GetTaskUseCase implements UseCaseInterface
{
    public function __construct(
        private GetTaskValidator $validator,
        private TaskProviderGateway $taskProviderGateway,
        private TaskOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $taskId): TaskDataOutput
    {
        $task = $this->taskProviderGateway->findOneById($taskId);
        if (null === $task) {
            throw new DataModelNotFoundException(TaskDataModel::class);
        }

        $this->validator->validate($currentUser, $task);

        return $this->outputFactory->buildOne($task);
    }
}
