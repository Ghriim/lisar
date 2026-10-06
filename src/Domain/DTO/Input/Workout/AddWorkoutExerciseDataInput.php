<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;

/**
 * A movement added to an existing block, after the ones already there — which is how a block
 * becomes a superset. That it is offered needs the database: WorkoutMovementsOfferedConstraint.
 */
final readonly class AddWorkoutExerciseDataInput implements DataInputInterface
{
    public function __construct(
        public int $movementId,
    ) {
    }
}
