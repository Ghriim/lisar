<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Correcting a set. The whole of it, every time: a measure or a set type left out is cleared, and
 * the same rules as adding one hold — except that a set type retired since may stay.
 */
final readonly class UpdateWorkoutSetDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'reps_invalid')]
        public ?int $reps = null,

        // Zero is a load: an empty machine, a bar on its own.
        #[Assert\Range(min: 0, max: 1000, notInRangeMessage: 'load_invalid')]
        public ?float $weightInKilograms = null,

        #[Assert\Range(min: 1, max: 86400, notInRangeMessage: 'duration_invalid')]
        public ?int $durationInSeconds = null,

        #[Assert\Range(min: 1, max: 1000000, notInRangeMessage: 'distance_invalid')]
        public ?int $distanceInMetres = null,

        #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'rpe_invalid')]
        #[Assert\DivisibleBy(value: 0.5, message: 'rpe_invalid')]
        public ?float $rpe = null,

        // None: an ordinary working set.
        public ?int $setTypeId = null,
    ) {
    }
}
