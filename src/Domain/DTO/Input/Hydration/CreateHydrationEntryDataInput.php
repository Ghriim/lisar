<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Hydration;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A volume, and nothing else. A shortcut is a way of filling this field in one tap, not a thing
 * the entry points at.
 */
final readonly class CreateHydrationEntryDataInput implements DataInputInterface
{
    public function __construct(
        // Bounded on both sides: a zero teaches nothing, and five litres in one go is a typo.
        #[Assert\Range(min: 1, max: 5000, notInRangeMessage: 'volume_invalid')]
        public int $volumeInMillilitres,
    ) {
    }
}
