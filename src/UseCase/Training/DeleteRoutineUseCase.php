<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\RoutinePersisterGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteRoutineValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Deleting one of the caller's routines, with all it holds. The workouts it started stay. */
final readonly class DeleteRoutineUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteRoutineValidator $validator,
        private RoutineProviderGateway $routineProviderGateway,
        private RoutinePersisterGateway $routinePersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $routineId): void
    {
        $routine = $this->routineProviderGateway->findOneById($routineId);
        if (null === $routine) {
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $this->validator->validate($currentUser, $routine);

        $this->routinePersisterGateway->delete($routine);
    }
}
