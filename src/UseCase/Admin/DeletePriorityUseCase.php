<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\PriorityPersisterGateway;
use App\Domain\Gateway\Provider\PriorityProviderGateway;
use App\Domain\Gateway\Provider\TaskProviderGateway;
use App\Domain\Validation\Constraint\Task\PriorityDeletableConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class DeletePriorityUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_priority_invalid';

    public function __construct(
        private PriorityProviderGateway $priorityProviderGateway,
        private PriorityPersisterGateway $priorityPersisterGateway,
        private TaskProviderGateway $taskProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $priority = $this->priorityProviderGateway->findOneById($id);
        if (null === $priority) {
            throw new DataModelNotFoundException(PriorityDataModel::class);
        }

        $violations = PriorityDeletableConstraint::validate(
            $priority,
            $this->taskProviderGateway->countForPriority($priority),
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->priorityPersisterGateway->delete($priority);
    }
}
