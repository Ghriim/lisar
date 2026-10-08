<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSummaryDataModel;
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
     * @param list<WorkoutDataModel>              $workouts
     * @param array<int, WorkoutSummaryDataModel> $summaries by workout id
     *
     * @return list<WorkoutSummaryDataOutput>
     */
    public function buildMany(array $workouts, array $summaries): array
    {
        $outputs = [];
        foreach ($workouts as $workout) {
            $outputs[] = $this->buildOne($workout, $summaries[(int) $workout->id] ?? null);
        }

        return $outputs;
    }

    /**
     * @param list<WorkoutDataModel>              $workouts
     * @param array<int, WorkoutSummaryDataModel> $summaries by workout id
     *
     * @return PaginatedListDataOutput<WorkoutSummaryDataOutput>
     */
    public function buildPaginated(array $workouts, array $summaries, int $total, int $page, int $perPage): PaginatedListDataOutput
    {
        /** @var PaginatedListDataOutput<WorkoutSummaryDataOutput> $output */
        $output = new PaginatedListDataOutput();
        $output->items = $this->buildMany($workouts, $summaries);
        $output->total = $total;
        $output->page = $page;
        $output->perPage = $perPage;

        return $output;
    }

    /** The totals are the summary's, as it stands; a workout without one reads as empty. */
    public function buildOne(WorkoutDataModel $workout, ?WorkoutSummaryDataModel $summary): WorkoutSummaryDataOutput
    {
        $output = $this->mapper->map($workout, WorkoutSummaryDataOutput::class);

        foreach ($workout->orderedBlocks() as $block) {
            foreach ($block->orderedExercises() as $exercise) {
                if (false === in_array($exercise->movement->name, $output->movementNames, true)) {
                    $output->movementNames[] = $exercise->movement->name;
                }
            }
        }

        if (null !== $summary) {
            $output->setCount = $summary->setCount;
            $output->volumeInKilograms = $summary->volumeInKilograms;
            $output->durationInSeconds = $summary->durationInSeconds;
            $output->distanceInMetres = $summary->distanceInMetres;
            $output->personalBestCount = $summary->personalBestCount;
        }

        return $output;
    }
}
