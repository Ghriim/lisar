<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Weight;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A weight, and nothing else. Not the day — that is always the one in progress, and there is no
 * way to name another.
 */
final readonly class SaveWeightDataInput implements DataInputInterface
{
    /** What the column holds, and therefore how much of what is typed is kept. */
    public const int DECIMALS = 2;

    public function __construct(
        // Not a medical judgement, a typo filter: a missed decimal point turns 72.4 into 724 and
        // would poison every average the statistics domain ever computes.
        #[Assert\Range(min: 20, max: 400, notInRangeMessage: 'weight_invalid')]
        public float $weightInKilograms,
    ) {
    }
}
