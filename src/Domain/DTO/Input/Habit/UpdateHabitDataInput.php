<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Habit;

use App\Domain\DTO\Input\DataInputInterface;
use App\Domain\Registry\Habit\HabitIconRegistry;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reconfiguring a catalogue habit. Same shape as creating one: a create and an update do not share
 * a DataInput, their rules diverge the day one of them gains a field.
 */
final readonly class UpdateHabitDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'name_required')]
        #[Assert\Length(max: 128, maxMessage: 'name_too_long')]
        public string $name,

        #[Assert\Choice(choices: HabitIconRegistry::ALL, message: 'icon_unknown')]
        public string $icon,

        #[Assert\Choice(choices: HabitSourceRegistry::ALL, message: 'source_unknown')]
        public string $sourceKind,

        public ?string $trackerKind = null,

        #[Assert\Range(min: 1, max: 1000000, notInRangeMessage: 'tracker_threshold_invalid')]
        public ?int $trackerThreshold = null,
    ) {
    }
}
