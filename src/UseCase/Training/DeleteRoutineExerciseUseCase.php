<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineBlockPersisterGateway;
use App\Domain\Gateway\Persister\Training\RoutineExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineExerciseProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\DeleteRoutineExerciseValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Removing a movement from a routine, with its sets. A block never stays empty: its last movement takes it along. */
final readonly class DeleteRoutineExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteRoutineExerciseValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutineExerciseProviderGateway $routineExerciseProviderGateway,
        private RoutineExercisePersisterGateway $routineExercisePersisterGateway,
        private RoutineBlockPersisterGateway $routineBlockPersisterGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private RoutineTree $routineTree,
        private RoutineOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $routineId, int $exerciseId): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $exercise = $this->routineExerciseProviderGateway->findOneById($exerciseId);
        if (null === $exercise) {
            throw new DataModelNotFoundException(RoutineExerciseDataModel::class);
        }

        $this->validator->validate($currentUser, $routine, $exercise);

        $block = $exercise->block;
        if (1 === $block->exercises->count()) {
            $this->routineBlockPersisterGateway->delete($block);
        } else {
            $this->routineExercisePersisterGateway->delete($exercise);
        }

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
