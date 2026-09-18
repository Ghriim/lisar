<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Hydration;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** Correcting an entry means correcting its volume. Its time is when it happened. */
final readonly class UpdateHydrationEntryDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Range(min: 1, max: 5000, notInRangeMessage: 'volume_invalid')]
        public int $volumeInMillilitres,
    ) {
    }
}
