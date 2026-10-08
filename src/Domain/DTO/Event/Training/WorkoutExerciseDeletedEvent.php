<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A movement was removed from a workout, its sets with it. Read before it went. */
final readonly class WorkoutExerciseDeletedEvent implements EventInterface
{
    public function __construct(
        public UserDataModel $owner,
        public MovementDataModel $movement,
    ) {
    }
}
