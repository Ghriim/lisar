<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A movement was removed from a workout, its sets with it. Read before it went. */
final readonly class WorkoutExerciseDeletedEvent implements EventInterface
{
    public function __construct(
        public WorkoutDataModel $workout,
        public MovementDataModel $movement,
        /** Whether one of its sets was done. */
        public bool $heldCompleteSets,
    ) {
    }
}
