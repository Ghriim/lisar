<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\Output\Training\RoutineSummaryDataOutput;
use App\Domain\Training\RoutineTree;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class RoutineSummaryOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private RoutineTree $routineTree,
    ) {
    }

    /**
     * @param list<RoutineDataModel> $routines
     *
     * @return list<RoutineSummaryDataOutput>
     */
    public function buildMany(array $routines): array
    {
        $outputs = [];
        foreach ($routines as $routine) {
            $outputs[] = $this->buildOne($routine);
        }

        return $outputs;
    }

    public function buildOne(RoutineDataModel $routine): RoutineSummaryDataOutput
    {
        $output = $this->mapper->map($routine, RoutineSummaryDataOutput::class);
        $output->movementCount = $this->routineTree->countExercises($routine);

        return $output;
    }
}
