<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Output\Workout\MuscleGroupDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class MuscleGroupOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<MuscleGroupDataModel> $muscleGroups
     *
     * @return list<MuscleGroupDataOutput>
     */
    public function buildMany(array $muscleGroups): array
    {
        $outputs = [];
        foreach ($muscleGroups as $muscleGroup) {
            $outputs[] = $this->buildOne($muscleGroup);
        }

        return $outputs;
    }

    public function buildOne(MuscleGroupDataModel $muscleGroup): MuscleGroupDataOutput
    {
        return $this->mapper->map($muscleGroup, MuscleGroupDataOutput::class);
    }
}
