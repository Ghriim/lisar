<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Output\Training\WorkoutCopyDataOutput;

final readonly class WorkoutCopyOutputFactory
{
    public function __construct(
        private WorkoutOutputFactory $workoutOutputFactory,
    ) {
    }

    /**
     * @param list<MovementDataModel> $skippedMovements
     */
    public function buildOne(WorkoutDataModel $copy, array $skippedMovements): WorkoutCopyDataOutput
    {
        $output = new WorkoutCopyDataOutput();
        $output->workout = $this->workoutOutputFactory->buildOne($copy);
        $output->skippedMovements = array_map(static fn (MovementDataModel $movement): string => $movement->name, $skippedMovements);

        return $output;
    }
}
