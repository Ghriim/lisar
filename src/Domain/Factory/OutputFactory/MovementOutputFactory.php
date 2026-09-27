<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\Output\Workout\MovementDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

use function strcasecmp;
use function usort;

final readonly class MovementOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private MuscleOutputFactory $muscleOutputFactory,
        private EquipmentOutputFactory $equipmentOutputFactory,
    ) {
    }

    /**
     * @param list<MovementDataModel> $movements
     *
     * @return list<MovementDataOutput>
     */
    public function buildMany(array $movements): array
    {
        $outputs = [];
        foreach ($movements as $movement) {
            $outputs[] = $this->buildOne($movement);
        }

        return $outputs;
    }

    public function buildOne(MovementDataModel $movement): MovementDataOutput
    {
        $output = $this->mapper->map($movement, MovementDataOutput::class);

        $output->primaryMuscle = $this->muscleOutputFactory->buildOne($movement->primaryMuscle);

        $secondaryMuscles = array_values($movement->secondaryMuscles->toArray());
        usort($secondaryMuscles, static fn (MuscleDataModel $a, MuscleDataModel $b): int => strcasecmp($a->name, $b->name));
        $output->secondaryMuscles = $this->muscleOutputFactory->buildMany($secondaryMuscles);

        $equipments = array_values($movement->equipments->toArray());
        usort($equipments, static fn (EquipmentDataModel $a, EquipmentDataModel $b): int => strcasecmp($a->name, $b->name));
        $output->equipments = $this->equipmentOutputFactory->buildMany($equipments);

        return $output;
    }
}
