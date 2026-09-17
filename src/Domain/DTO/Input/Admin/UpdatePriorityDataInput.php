<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdatePriorityDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'label_required')]
        #[Assert\Length(max: 32, maxMessage: 'label_too_long')]
        public string $label,

        #[Assert\Range(min: 0, max: 9999, notInRangeMessage: 'weight_invalid')]
        public int $weight = 0,

        #[Assert\NotBlank(message: 'colour_required')]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'colour_invalid')]
        public string $colour = '#3e63dd',

        public bool $isDefault = false,
    ) {
    }
}
