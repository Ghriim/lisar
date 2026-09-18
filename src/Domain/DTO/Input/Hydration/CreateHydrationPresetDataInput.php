<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Hydration;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Hydration\HydrationIconRegistry;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateHydrationPresetDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Choice(choices: HydrationIconRegistry::ALL, message: 'icon_unknown')]
        public string $icon,

        #[Assert\Range(min: 1, max: 5000, notInRangeMessage: 'volume_invalid')]
        public int $volumeInMillilitres,
    ) {
    }
}
