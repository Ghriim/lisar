<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteMovementFamilyValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a movement family, once it is empty. A family still holding movements — anyone's,
 * active or not — is refused: every movement sits in a family, and the answer to "retire it
 * anyway" is deactivating it.
 */
final readonly class DeleteMovementFamilyUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteMovementFamilyValidator $validator,
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MovementFamilyPersisterGateway $movementFamilyPersisterGateway,
        private MovementProviderGateway $movementProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $movementFamily = $this->movementFamilyProviderGateway->findOneById($id);
        if (null === $movementFamily) {
            throw new DataModelNotFoundException(MovementFamilyDataModel::class);
        }

        $this->validator->validate($currentUser, $this->movementProviderGateway->countForMovementFamily($movementFamily));

        $this->movementFamilyPersisterGateway->delete($movementFamily);
    }
}
