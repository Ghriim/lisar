<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteWorkoutValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a workout, with everything logged in it. For the one in progress this is abandoning
 * it; for a finished one, the habits fed by the workout tracker recount its day.
 */
final readonly class DeleteWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteWorkoutValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPersisterGateway $workoutPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId): void
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($currentUser, $workout);

        // The habits watching workouts, and the records, follow on the persister's event.
        $this->workoutPersisterGateway->delete($workout);
    }
}
