<?php

declare(strict_types=1);

namespace App\UseCase\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Todo\PriorityPersisterGateway;
use App\Domain\Gateway\Provider\Todo\PriorityProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Validation\Validator\Todo\Admin\DeletePriorityValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeletePriorityUseCase implements UseCaseInterface
{
    public function __construct(
        private DeletePriorityValidator $validator,
        private PriorityProviderGateway $priorityProviderGateway,
        private PriorityPersisterGateway $priorityPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $priority = $this->priorityProviderGateway->findOneById($id);
        if (null === $priority) {
            throw new DataModelNotFoundException(PriorityDataModel::class);
        }

        $this->validator->validate($currentUser, $priority, $this->taskProviderGateway->countForPriority($priority));

        $this->priorityPersisterGateway->delete($priority);
    }
}
