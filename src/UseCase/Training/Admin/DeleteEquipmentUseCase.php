<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Constraint\Training\EquipmentUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting an equipment no movement is done with. One a movement uses is refused — anyone's
 * movement, not only the common ones: deactivating is the way to retire it.
 */
final readonly class DeleteEquipmentUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_equipment_invalid';

    public function __construct(
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
        private MovementProviderGateway $movementProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $equipment = $this->equipmentProviderGateway->findOneById($id);
        if (null === $equipment) {
            throw new DataModelNotFoundException(EquipmentDataModel::class);
        }

        $violations = EquipmentUnusedConstraint::validate($this->movementProviderGateway->countForEquipment($equipment));

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->equipmentPersisterGateway->delete($equipment);
    }
}
