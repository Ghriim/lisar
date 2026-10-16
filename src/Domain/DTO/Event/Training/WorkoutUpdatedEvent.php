<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A workout was renamed, noted, felt — or finished. */
final readonly class WorkoutUpdatedEvent implements EventInterface
{
    public function __construct(
        public WorkoutDataModel $workout,
        /** Whether this write is the one finishing it: the only change its length and its day follow. */
        public bool $finishes,
    ) {
    }
}
