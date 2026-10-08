<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** One movement of a block being added, with the rest planned after each of its sets. */
final readonly class AddWorkoutBlockExerciseDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Type(type: 'int', message: 'movement_id_invalid')]
        public int $movementId,

        // None: no timer after its sets. An hour is a typo filter, not a training opinion.
        #[Assert\Range(min: 1, max: 3600, notInRangeMessage: 'rest_invalid')]
        public ?int $restInSeconds = null,
    ) {
    }
}
