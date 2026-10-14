<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteMovementValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a common movement no workout logged. One a workout used is refused — anyone's workout:
 * deactivating is the way to retire it, and the history keeps it.
 */
final readonly class DeleteMovementUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteMovementValidator $validator,
        private MovementProviderGateway $movementProviderGateway,
        private MovementPersisterGateway $movementPersisterGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $movement = $this->movementProviderGateway->findOneCommonById($id);
        if (null === $movement) {
            throw new DataModelNotFoundException(MovementDataModel::class);
        }

        $this->validator->validate($currentUser, $this->workoutProviderGateway->countExercisesForMovement($movement));

        $this->movementPersisterGateway->delete($movement);
    }
}
