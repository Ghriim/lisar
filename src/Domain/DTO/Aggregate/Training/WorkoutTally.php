<?php

declare(strict_types=1);

namespace App\Domain\DTO\Aggregate\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;

/** What one workout's sets that count for personal bests add up to, across its movements. */
final readonly class WorkoutTally
{
    public function __construct(
        public WorkoutDataModel $workout,
        public int $setCount,
        /** Reps times load over the sets carrying both; null when none does. */
        public ?float $volumeInKilograms,
    ) {
    }
}
