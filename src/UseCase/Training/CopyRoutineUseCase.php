<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Training\RoutineDataModelFactory;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Persister\Training\RoutinePersisterGateway;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\CopyRoutineValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** A new routine of the caller's, « Copie de … », holding everything one of their routines holds. */
final readonly class CopyRoutineUseCase implements UseCaseInterface
{
    public function __construct(
        private CopyRoutineValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
        private RoutinePersisterGateway $routinePersisterGateway,
        private RoutineDataModelFactory $routineDataModelFactory,
        private RoutineTree $routineTree,
        private RoutineOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $routineId): RoutineDataOutput
    {
        $source = $this->routineProviderGateway->findOneById($routineId);
        if (null === $source) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $this->validator->validate($currentUser, $source);

        $copy = $this->routineDataModelFactory->buildCopy($source, $currentUser);
        $this->routinePersisterGateway->create($copy);

        return $this->outputFactory->buildOne($copy, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($copy)));
    }
}
