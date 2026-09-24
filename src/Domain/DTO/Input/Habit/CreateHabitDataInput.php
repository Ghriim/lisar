<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Habit;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Habit\HabitIconRegistry;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A catalogue habit an administrator defines. The shape rules are attributes; that a tracker habit
 * names a tracker and a mark is a coherence rule, in HabitSourceConstraint.
 */
final readonly class CreateHabitDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        #[Assert\Choice(choices: HabitIconRegistry::ALL, message: 'icon_unknown')]
        public string $icon,

        #[Assert\Choice(choices: HabitSourceRegistry::ALL, message: 'source_unknown')]
        public string $sourceKind,

        // Only meaningful for a tracker habit; the coherence rule guards that pairing.
        public ?string $trackerKind = null,

        // Upper bound is a sanity ceiling, not a business rule.
        #[Assert\Range(min: 1, max: 1000000, notInRangeMessage: 'tracker_threshold_invalid')]
        public ?int $trackerThreshold = null,
    ) {
    }
}
