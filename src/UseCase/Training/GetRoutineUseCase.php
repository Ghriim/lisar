<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\RoutineOutputFactory;
use App\Domain\Gateway\Provider\Training\PersonalBestProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Training\RoutineTree;
use App\Domain\Validation\Validator\Training\GetRoutineValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** One of the caller's routines, whole, each set with the load it would give today. */
final readonly class GetRoutineUseCase implements UseCaseInterface
{
    public function __construct(
        private GetRoutineValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private PersonalBestProviderGateway $personalBestProviderGateway,
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
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $this->validator->validate($currentUser, $routine);

        return $this->outputFactory->buildOne($routine, $this->personalBestProviderGateway->findEstimatedOneRepMaxesForOwner($currentUser, $this->routineTree->movements($routine)));
    }
}
