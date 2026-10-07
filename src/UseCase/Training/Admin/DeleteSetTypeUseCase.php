<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a set type no logged set carries. One a set carries is refused — anyone's set:
 * deactivating is the way to retire it. The default is refused too: it is given to another type
 * first.
 */
final readonly class DeleteSetTypeUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_set_type_invalid';

    public function __construct(
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypePersisterGateway $setTypePersisterGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $setType = $this->setTypeProviderGateway->findOneById($id);
        if (null === $setType) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        $violations = SetTypeUnusedConstraint::validate($this->workoutProviderGateway->countSetsForSetType($setType));
        $violations = SetTypeNotDefaultConstraint::validate($setType, $violations);

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->setTypePersisterGateway->delete($setType);
    }
}
