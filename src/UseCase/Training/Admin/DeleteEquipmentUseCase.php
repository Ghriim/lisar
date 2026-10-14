<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Validator\Training\DeleteEquipmentValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting an equipment no movement is done with. One a movement uses is refused — anyone's
 * movement, not only the common ones: deactivating is the way to retire it.
 */
final readonly class DeleteEquipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteEquipmentValidator $validator,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
        private MovementProviderGateway $movementProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $id): void
    {
        $equipment = $this->equipmentProviderGateway->findOneById($id);
        if (null === $equipment) {
            throw new DataModelNotFoundException(EquipmentDataModel::class);
        }

        $this->validator->validate($currentUser, $this->movementProviderGateway->countForEquipment($equipment));

        $this->equipmentPersisterGateway->delete($equipment);
    }
}
