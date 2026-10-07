<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\Input\Training\UpdateEquipmentDataInput;
use App\Domain\DTO\Output\Training\EquipmentDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\EquipmentOutputFactory;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateEquipmentValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Reconfiguring an equipment. Its flags change what the next movements track; nothing logged is
 * rewritten.
 */
final readonly class UpdateEquipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateEquipmentValidator $validator,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
        private EquipmentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateEquipmentDataInput $input): EquipmentDataOutput
    {
        $equipment = $this->equipmentProviderGateway->findOneById($id);
        if (null === $equipment) {
            throw new DataModelNotFoundException(EquipmentDataModel::class);
        }

        $this->validator->validate($input, $equipment, $this->equipmentProviderGateway->findOneByName($input->name));
        $equipment->name = $input->name;
        $equipment->hasWeight = $input->hasWeight;
        $equipment->hasDistance = $input->hasDistance;

        $this->equipmentPersisterGateway->update($equipment);

        return $this->outputFactory->buildOne($equipment);
    }
}
