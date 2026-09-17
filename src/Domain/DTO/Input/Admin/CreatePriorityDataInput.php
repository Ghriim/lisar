<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Admin;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A priority level. The set is global: everyone picks from the same one, and only the
 * back-office writes it.
 */
final readonly class CreatePriorityDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'label_required')]
        // Upper bound imposed by the column, not a business rule.
        #[Assert\Length(max: 32, maxMessage: 'label_too_long')]
        public string $label,

        // Gives the order inside a category. Lower sorts first; the bound is arbitrary and only
        // there to keep the values human.
        #[Assert\Range(min: 0, max: 9999, notInRangeMessage: 'weight_invalid')]
        public int $weight = 0,

        #[Assert\NotBlank(message: 'colour_required')]
        #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'colour_invalid')]
        public string $colour = '#3e63dd',

        /** Only ever set to true: the default moves by being given to another priority. */
        public bool $isDefault = false,
    ) {
    }
}
