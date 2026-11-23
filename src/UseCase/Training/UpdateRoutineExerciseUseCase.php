<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineExerciseDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineExerciseProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\UpdateRoutineExerciseValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** The note and the rest of a movement of a routine, handed to the workouts it starts. */
final readonly class UpdateRoutineExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateRoutineExerciseValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutineExerciseProviderGateway $routineExerciseProviderGateway,
        private RoutineExercisePersisterGateway $routineExercisePersisterGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private RoutineTree $routineTree,
        private RoutineOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $routineId, int $exerciseId, UpdateRoutineExerciseDataInput $input): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $exercise = $this->routineExerciseProviderGateway->findOneById($exerciseId);
        if (null === $exercise) {
            throw new DataModelNotFoundException(RoutineExerciseDataModel::class);
        }

        $this->validator->validate($currentUser, $routine, $exercise, $input);

        // Blank means none.
        $exercise->note = '' === $input->note ? null : $input->note;
        $exercise->restInSeconds = $input->restInSeconds;
        $this->routineExercisePersisterGateway->update($exercise);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
