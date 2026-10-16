<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A set was corrected, ticked or unticked. */
final readonly class WorkoutSetUpdatedEvent implements EventInterface
{
    public function __construct(
        public WorkoutSetDataModel $set,
        /** Whether it counted for the personal bests before the write: done, of a type that counts. */
        public bool $countedBefore,
    ) {
    }
}
