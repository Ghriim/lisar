<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\MuscleGroupPersisterGateway;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use App\Domain\Validation\Constraint\Workout\MuscleGroupUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a muscle group, once it is empty. A group still holding muscles — active or not — is
 * refused: every muscle sits in a group, and the answer to "retire it anyway" is deactivating it.
 */
final readonly class DeleteMuscleGroupUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_muscle_group_invalid';

    public function __construct(
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MuscleGroupPersisterGateway $muscleGroupPersisterGateway,
        private MuscleProviderGateway $muscleProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $muscleGroup = $this->muscleGroupProviderGateway->findOneById($id);
        if (null === $muscleGroup) {
            throw new DataModelNotFoundException(MuscleGroupDataModel::class);
        }

        $violations = MuscleGroupUnusedConstraint::validate(
            $this->muscleProviderGateway->countForMuscleGroup($muscleGroup),
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->muscleGroupPersisterGateway->delete($muscleGroup);
    }
}
