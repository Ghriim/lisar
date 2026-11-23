<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineSetDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineSetProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\UpdateRoutineSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Correcting a prescribed set: its targets, replaced whole. */
final readonly class UpdateRoutineSetUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateRoutineSetValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutineSetProviderGateway $routineSetProviderGateway,
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
    public function execute(UserDataModel $currentUser, int $routineId, int $setId, UpdateRoutineSetDataInput $input): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $set = $this->routineSetProviderGateway->findOneById($setId);
        if (null === $set) {
            throw new DataModelNotFoundException(RoutineSetDataModel::class);
        }

        $this->validator->validate($currentUser, $routine, $set, $input);

        $set->reps = $input->reps;
        $set->percentageOfOneRepMax = $input->percentageOfOneRepMax;
        $this->routineSetPersisterGateway->update($set);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
