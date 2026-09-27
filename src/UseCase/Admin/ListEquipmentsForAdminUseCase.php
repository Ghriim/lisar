<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\Input\Admin\ListEquipmentsForAdminDataInput;
use App\Domain\DTO\Output\Workout\EquipmentDataOutput;
use App\Domain\Factory\OutputFactory\EquipmentOutputFactory;
use App\Domain\Gateway\Provider\EquipmentProviderGateway;
use App\UseCase\UseCaseInterface;

/** Every equipment for the back-office, active and retired alike, by name. Not paginated: reference data an administrator keeps in a few dozen rows. */
final readonly class ListEquipmentsForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private EquipmentProviderGateway $equipmentProviderGateway,
        private EquipmentOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<EquipmentDataOutput>
     */
    public function execute(ListEquipmentsForAdminDataInput $input = new ListEquipmentsForAdminDataInput()): array
    {
        return $this->outputFactory->buildMany($this->equipmentProviderGateway->findAllForAdminList(
            $input->isActive, $input->hasWeight, $input->hasDistance,
        ));
    }
}
