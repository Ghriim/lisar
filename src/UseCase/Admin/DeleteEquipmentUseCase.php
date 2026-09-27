<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\Gateway\Persister\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\EquipmentProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting an equipment. Nothing refers to one yet; the day movements do, this is where the
 * in-use check goes — and until then, deactivating is the way to retire one that has been used.
 */
final readonly class DeleteEquipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): void
    {
        $equipment = $this->equipmentProviderGateway->findOneById($id);
        if (null === $equipment) {
            throw new DataModelNotFoundException(EquipmentDataModel::class);
        }

        $this->equipmentPersisterGateway->delete($equipment);
    }
}
