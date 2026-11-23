<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A set prescribed on a movement of a routine, after the ones already there. Which targets it must
 * carry depends on the movement: RoutineSetPrescriptionConstraint.
 */
final readonly class AddRoutineSetDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Range(min: 1, max: 10000, notInRangeMessage: 'reps_invalid')]
        public ?int $reps = null,

        // A share of the movement's estimated 1RM, by halves.
        #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'percentage_invalid')]
        #[Assert\DivisibleBy(value: 0.5, message: 'percentage_invalid')]
        public ?float $percentageOfOneRepMax = null,
    ) {
    }
}
