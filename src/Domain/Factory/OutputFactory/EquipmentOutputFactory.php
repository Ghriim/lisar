<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\Output\Workout\EquipmentDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class EquipmentOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<EquipmentDataModel> $equipments
     *
     * @return list<EquipmentDataOutput>
     */
    public function buildMany(array $equipments): array
    {
        $outputs = [];
        foreach ($equipments as $equipment) {
            $outputs[] = $this->buildOne($equipment);
        }

        return $outputs;
    }

    public function buildOne(EquipmentDataModel $equipment): EquipmentDataOutput
    {
        return $this->mapper->map($equipment, EquipmentDataOutput::class);
    }
}
