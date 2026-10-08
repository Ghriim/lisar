<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A movement added to an existing block, after the ones already there — which is how a block
 * becomes a superset. That it is offered needs the database: WorkoutMovementsOfferedConstraint.
 */
final readonly class AddWorkoutExerciseDataInput implements DataInputInterface
{
    public function __construct(
        public int $movementId,

        // None: no timer after its sets. An hour is a typo filter, not a training opinion.
        #[Assert\Range(min: 1, max: 3600, notInRangeMessage: 'rest_invalid')]
        public ?int $restInSeconds = null,
    ) {
    }
}
