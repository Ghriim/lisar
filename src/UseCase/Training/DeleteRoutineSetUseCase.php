<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutineSetPersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineSetProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\DeleteRoutineSetValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Removing a prescribed set. A movement may stay without any: it is filled in later. */
final readonly class DeleteRoutineSetUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteRoutineSetValidator $validator,
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
    public function execute(UserDataModel $currentUser, int $routineId, int $setId): RoutineDataOutput
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $set = $this->routineSetProviderGateway->findOneById($setId);
        if (null === $set) {
            throw new DataModelNotFoundException(RoutineSetDataModel::class);
        }

        $this->validator->validate($currentUser, $routine, $set);

        $this->routineSetPersisterGateway->delete($set);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
