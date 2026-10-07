<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\Input\Training\CreateEquipmentDataInput;
use App\Domain\DTO\Output\Training\EquipmentDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\EquipmentOutputFactory;
use App\Domain\Gateway\Persister\Training\EquipmentPersisterGateway;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Validation\Validator\Training\CreateEquipmentValidator;
use App\UseCase\UseCaseInterface;

/**
 * Adding an equipment, offered to new movements at once.
 */
final readonly class CreateEquipmentUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateEquipmentValidator $validator,
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentPersisterGateway $equipmentPersisterGateway,
        private EquipmentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateEquipmentDataInput $input): EquipmentDataOutput
    {
        $this->validator->validate($input, $this->equipmentProviderGateway->findOneByName($input->name));

        $equipment = new EquipmentDataModel();
        $equipment->name = $input->name;
        $equipment->hasWeight = $input->hasWeight;
        $equipment->hasDistance = $input->hasDistance;

        $this->equipmentPersisterGateway->create($equipment);

        return $this->outputFactory->buildOne($equipment);
    }
}
