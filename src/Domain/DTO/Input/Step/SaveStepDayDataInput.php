<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Step;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A day's step count, and nothing else. Not the day — that is always the one in progress, and
 * there is no way to name another. Not the source either — a manual save is a manual save; the
 * mobile sync to come will set it for itself.
 */
final readonly class SaveStepDayDataInput implements DataInputInterface
{
    public function __construct(
        // A running total, not an increment. Zero is a real answer — a day one did not walk — so
        // the lower bound is the count itself; the upper one is a typo filter: nobody walks past
        // it in a day, and a pasted sensor reading that does is not a step count.
        #[Assert\Range(min: 0, max: 200000, notInRangeMessage: 'count_invalid')]
        public int $countInSteps,
    ) {
    }
}
