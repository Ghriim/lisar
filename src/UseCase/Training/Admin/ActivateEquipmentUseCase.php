<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\Output\Training\EquipmentDataOutput;
use App\Domain\Factory\OutputFactory\Training\EquipmentOutputFactory;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Offering a retired equipment to new movements again.
 */
final readonly class ActivateEquipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
        private EquipmentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): EquipmentDataOutput
    {
        $equipment = $this->equipmentProviderGateway->findOneById($id);
        if (null === $equipment) {
            throw new DataModelNotFoundException(EquipmentDataModel::class);
        }

        $equipment->isActive = true;

        $this->equipmentPersisterGateway->update($equipment);

        return $this->outputFactory->buildOne($equipment);
    }
}
