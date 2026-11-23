<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\ReorderRoutineBlocksDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineBlockPersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\ReorderRoutineBlocksValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Putting a routine's blocks in a new order: every block, each once. */
final readonly class ReorderRoutineBlocksUseCase implements UseCaseInterface
{
    public function __construct(
        private ReorderRoutineBlocksValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
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
    public function execute(UserDataModel $currentUser, int $routineId, ReorderRoutineBlocksDataInput $input): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $this->validator->validate($currentUser, $input, $routine);

        // The validator saw to it that the list names every block of the routine, each once.
        $positions = array_flip($input->blockIds);
        foreach ($routine->blocks as $block) {
            $position = $positions[$block->id] ?? $block->position;
            if ($position !== $block->position) {
                $block->position = $position;
                $this->routineBlockPersisterGateway->update($block);
            }
        }

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
