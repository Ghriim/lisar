<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Output\PaginatedListDataOutput;
use App\Domain\DTO\Output\Training\WorkoutSummaryDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

use function in_array;

final readonly class WorkoutSummaryOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<WorkoutDataModel> $workouts
     *
     * @return list<WorkoutSummaryDataOutput>
     */
    public function buildMany(array $workouts): array
    {
        $outputs = [];
        foreach ($workouts as $workout) {
            $outputs[] = $this->buildOne($workout);
        }

        return $outputs;
    }

    /**
     * @param list<WorkoutDataModel> $workouts
     *
     * @return PaginatedListDataOutput<WorkoutSummaryDataOutput>
     */
    public function buildPaginated(array $workouts, int $total, int $page, int $perPage): PaginatedListDataOutput
    {
        /** @var PaginatedListDataOutput<WorkoutSummaryDataOutput> $output */
        $output = new PaginatedListDataOutput();
        $output->items = $this->buildMany($workouts);
        $output->total = $total;
        $output->page = $page;
        $output->perPage = $perPage;

        return $output;
    }

    public function buildOne(WorkoutDataModel $workout): WorkoutSummaryDataOutput
    {
        $output = $this->mapper->map($workout, WorkoutSummaryDataOutput::class);

        foreach ($workout->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                if (false === in_array($exercise->movement->name, $output->movementNames, true)) {
                    $output->movementNames[] = $exercise->movement->name;
                }
            }
        }
        $output->setCount = $workout->countSets();

        return $output;
    }
}
