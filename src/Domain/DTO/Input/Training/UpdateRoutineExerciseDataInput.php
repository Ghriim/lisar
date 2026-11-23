<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** The note on a movement of a routine, and its rest. Either left out, or blank, clears it. */
final readonly class UpdateRoutineExerciseDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Length(max: 5000, maxMessage: 'note_too_long')]
        public ?string $note = null,

        // None: no timer after its sets. An hour is a typo filter, not a training opinion.
        #[Assert\Range(min: 1, max: 3600, notInRangeMessage: 'rest_invalid')]
        public ?int $restInSeconds = null,
    ) {
    }
}
