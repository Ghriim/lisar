<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineExerciseProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\AddRoutineSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Prescribing a set on a movement of a routine, after the ones already there. */
final readonly class AddRoutineSetUseCase implements UseCaseInterface
{
    public function __construct(
        private AddRoutineSetValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutineExerciseProviderGateway $routineExerciseProviderGateway,
        private RoutineSetPersisterGateway $routineSetPersisterGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private RoutineTree $routineTree,
        private RoutineOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $routineId, int $exerciseId, AddRoutineSetDataInput $input): RoutineDataOutput
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

        $set = new RoutineSetDataModel();
        $set->exercise = $exercise;
        $set->position = $this->routineTree->nextSetPosition($exercise);
        $set->reps = $input->reps;
        $set->percentageOfOneRepMax = $input->percentageOfOneRepMax;
        $this->routineSetPersisterGateway->create($set);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
