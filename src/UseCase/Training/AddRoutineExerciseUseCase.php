<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineExerciseDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineExercisePersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineBlockProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\AddRoutineExerciseValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Adding a movement to a block of a routine, after the ones already there — which is how a block
 * becomes a superset. The movement must be offered now and fit a routine.
 */
final readonly class AddRoutineExerciseUseCase implements UseCaseInterface
{
    public function __construct(
        private AddRoutineExerciseValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutineBlockProviderGateway $routineBlockProviderGateway,
        private MovementProviderGateway $movementProviderGateway,
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
    public function execute(UserDataModel $currentUser, int $routineId, int $blockId, AddRoutineExerciseDataInput $input): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $block = $this->routineBlockProviderGateway->findOneById($blockId);
        if (null === $block) {
            throw new DataModelNotFoundException(RoutineBlockDataModel::class);
        }

        $movement = $this->movementProviderGateway->findOneOfferedById($input->movementId);
        $this->validator->validate($currentUser, $routine, $block, $input, $movement);
        if (null === $movement) {
            // Unreachable: the validator refuses a movement that is not offered.
            throw new DataModelNotFoundException(RoutineExerciseDataModel::class);
        }

        $exercise = new RoutineExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $movement;
        $exercise->position = $this->routineTree->nextExercisePosition($block);
        $exercise->restInSeconds = $input->restInSeconds;
        $this->routineExercisePersisterGateway->create($exercise);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
