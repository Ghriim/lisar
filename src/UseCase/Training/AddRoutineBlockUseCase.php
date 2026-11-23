<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineBlockDataInput;
use App\Domain\DTO\Input\Training\AddRoutineBlockExerciseDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineBlockPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\AddRoutineBlockValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Adding a block at the end of a routine: one movement, or a superset in the order given. Each
 * movement must be offered now and fit a routine.
 */
final readonly class AddRoutineBlockUseCase implements UseCaseInterface
{
    public function __construct(
        private AddRoutineBlockValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private MovementProviderGateway $movementProviderGateway,
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
    public function execute(UserDataModel $currentUser, int $routineId, AddRoutineBlockDataInput $input): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $movementIds = array_map(static fn (AddRoutineBlockExerciseDataInput $exercise): int => $exercise->movementId, $input->exercises);
        $offered = $this->movementProviderGateway->findAllOfferedByIds($movementIds);

        $this->validator->validate($currentUser, $routine, $input, $offered);

        $offeredById = [];
        foreach ($offered as $movement) {
            $offeredById[(int) $movement->id] = $movement;
        }

        $block = new RoutineBlockDataModel();
        $block->routine = $routine;
        $block->position = $this->routineTree->nextBlockPosition($routine);

        // Every movement asked for is offered past the validator; the block takes them in the
        // order they were asked for, not the one the database gave them back in. Persisted with
        // the block, in one flush.
        foreach ($input->exercises as $position => $requested) {
            $exercise = new RoutineExerciseDataModel();
            $exercise->block = $block;
            $exercise->movement = $offeredById[$requested->movementId];
            $exercise->position = $position;
            $exercise->restInSeconds = $requested->restInSeconds;
            $block->exercises->add($exercise);
        }

        $this->routineBlockPersisterGateway->create($block);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
