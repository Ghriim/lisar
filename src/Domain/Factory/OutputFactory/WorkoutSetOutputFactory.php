<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\WorkoutSetDataModel;
use App\Domain\DTO\Output\Workout\WorkoutSetDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class WorkoutSetOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private SetTypeOutputFactory $setTypeOutputFactory,
    ) {
    }

    /**
     * @param iterable<WorkoutSetDataModel> $sets
     *
     * @return list<WorkoutSetDataOutput>
     */
    public function buildMany(iterable $sets): array
    {
        $outputs = [];
        foreach ($sets as $set) {
            $outputs[] = $this->buildOne($set);
        }

        return $outputs;
    }

    public function buildOne(WorkoutSetDataModel $set): WorkoutSetDataOutput
    {
        $output = $this->mapper->map($set, WorkoutSetDataOutput::class);

        if (null !== $set->setType) {
            $output->setType = $this->setTypeOutputFactory->buildOne($set->setType);
        }

        return $output;
    }
}
