<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\MovementPersisterGateway;
use App\Domain\Gateway\Provider\MovementProviderGateway;
use App\Domain\Gateway\Provider\WorkoutProviderGateway;
use App\Domain\Validation\Constraint\Workout\MovementUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a common movement no workout logged. One a workout used is refused — anyone's workout:
 * deactivating is the way to retire it, and the history keeps it.
 */
final readonly class DeleteMovementUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_movement_invalid';

    public function __construct(
        private MovementProviderGateway $movementProviderGateway,
        private MovementPersisterGateway $movementPersisterGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $movement = $this->movementProviderGateway->findOneCommonById($id);
        if (null === $movement) {
            throw new DataModelNotFoundException(MovementDataModel::class);
        }

        $violations = MovementUnusedConstraint::validate($this->workoutProviderGateway->countExercisesForMovement($movement));

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->movementPersisterGateway->delete($movement);
    }
}
