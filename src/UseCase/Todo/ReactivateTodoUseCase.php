<?php

declare(strict_types=1);

namespace App\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Todo\TodoDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Todo\TodoOutputFactory;
use App\Domain\Gateway\Persister\Todo\TodoPersisterGateway;
use App\Domain\Gateway\Provider\Todo\TodoProviderGateway;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Validator\Todo\ReactivateTodoValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Taking an archived TODO back out: its tasks show again. Its planning does not come back — the
 * entries went with the archiving.
 */
final readonly class ReactivateTodoUseCase implements UseCaseInterface
{
    public function __construct(
        private ReactivateTodoValidator $validator,
        private TodoProviderGateway $todoProviderGateway,
        private TodoPersisterGateway $todoPersisterGateway,
        private TodoOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $todoId): TodoDataOutput
    {
        $todo = $this->todoProviderGateway->findOneById($todoId);
        if (null === $todo) {
            throw new DataModelNotFoundException(TodoDataModel::class);
        }

        $this->validator->validate($currentUser, $todo);

        if (TodoStatusRegistry::ACTIVE === $todo->status) {
            // Already active: the caller wanted it so and it is.
            return $this->outputFactory->buildOne($todo);
        }

        $todo->status = TodoStatusRegistry::ACTIVE;
        $this->todoPersisterGateway->update($todo);

        return $this->outputFactory->buildOne($todo);
    }
}
