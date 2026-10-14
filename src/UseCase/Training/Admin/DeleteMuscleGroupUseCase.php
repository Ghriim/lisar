<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\MuscleGroupPersisterGateway;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteMuscleGroupValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a muscle group, once it is empty. A group still holding muscles — active or not — is
 * refused: every muscle sits in a group, and the answer to "retire it anyway" is deactivating it.
 */
final readonly class DeleteMuscleGroupUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteMuscleGroupValidator $validator,
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MuscleGroupPersisterGateway $muscleGroupPersisterGateway,
        private MuscleProviderGateway $muscleProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $muscleGroup = $this->muscleGroupProviderGateway->findOneById($id);
        if (null === $muscleGroup) {
            throw new DataModelNotFoundException(MuscleGroupDataModel::class);
        }

        $this->validator->validate($currentUser, $this->muscleProviderGateway->countForMuscleGroup($muscleGroup));

        $this->muscleGroupPersisterGateway->delete($muscleGroup);
    }
}
