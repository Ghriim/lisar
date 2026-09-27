<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\Output\Workout\MuscleDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class MuscleOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<MuscleDataModel> $muscles
     *
     * @return list<MuscleDataOutput>
     */
    public function buildMany(array $muscles): array
    {
        $outputs = [];
        foreach ($muscles as $muscle) {
            $outputs[] = $this->buildOne($muscle);
        }

        return $outputs;
    }

    public function buildOne(MuscleDataModel $muscle): MuscleDataOutput
    {
        return $this->mapper->map($muscle, MuscleDataOutput::class);
    }
}
