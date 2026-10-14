<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteMuscleValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a muscle no movement targets, as primary or secondary. One in use is refused —
 * anyone's movement, not only the common ones: deactivating is the way to retire it.
 */
final readonly class DeleteMuscleUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteMuscleValidator $validator,
        private MuscleProviderGateway $muscleProviderGateway,
        private MusclePersisterGateway $musclePersisterGateway,
        private MovementProviderGateway $movementProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $muscle = $this->muscleProviderGateway->findOneById($id);
        if (null === $muscle) {
            throw new DataModelNotFoundException(MuscleDataModel::class);
        }

        $this->validator->validate($currentUser, $this->movementProviderGateway->countForMuscle($muscle));

        $this->musclePersisterGateway->delete($muscle);
    }
}
