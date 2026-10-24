<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A set was removed. What it belonged to is read before it went. */
final readonly class WorkoutSetDeletedEvent implements EventInterface
{
    public function __construct(
        public WorkoutDataModel $workout,
        public MovementDataModel $movement,
        /** Whether it was done. */
        public bool $wasComplete,
    ) {
    }
}
