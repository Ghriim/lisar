<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\EquipmentDataOutput;
use App\Domain\Factory\OutputFactory\Training\EquipmentOutputFactory;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Validation\Validator\Training\DeactivateEquipmentValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Retiring an equipment. It stops being offered to new movements; the movements already done
 * with it keep it.
 */
final readonly class DeactivateEquipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private DeactivateEquipmentValidator $validator,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
        private EquipmentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser, int $id): EquipmentDataOutput
    {
        $equipment = $this->equipmentProviderGateway->findOneById($id);
        if (null === $equipment) {
            throw new DataModelNotFoundException(EquipmentDataModel::class);
        }

        $this->validator->validate($currentUser);

        $equipment->isActive = false;

        $this->equipmentPersisterGateway->update($equipment);

        return $this->outputFactory->buildOne($equipment);
    }
}
