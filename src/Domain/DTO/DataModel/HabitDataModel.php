<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A habit in the catalogue: a definition an administrator maintains, that people subscribe to. It
 * is reference data, like a hydration shortcut — not something a person creates yet.
 *
 * How it is kept lives here. A `manual` habit is ticked by hand and is binary: a day is kept or it
 * is not. A `tracker` habit names one of HabitTrackerRegistry's trackers and a `trackerThreshold`;
 * it is kept the day that tracker's figure reaches the mark.
 *
 * Removing a habit deactivates it rather than deleting the row: the days people already kept — and
 * the experience they will have earned — outlive the catalogue entry, so the row stays to carry
 * them.
 */
#[ORM\Table(name: 'habit')]
#[ORM\Entity]
class HabitDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 128)]
    public string $name;

    // One of HabitIconRegistry's codes; each front end draws its own glyph for it.
    #[ORM\Column(length: 32)]
    public string $icon;

    // manual | tracker — see HabitSourceRegistry.
    #[ORM\Column(length: 16)]
    public string $sourceKind;

    // The tracker that keeps it, one of HabitTrackerRegistry's; null for a manual habit.
    #[ORM\Column(length: 32, nullable: true)]
    public ?string $trackerKind = null;

    // The figure the tracker must reach that day for the habit to count as kept; null when manual.
    #[ORM\Column(nullable: true)]
    public ?int $trackerThreshold = null;

    // Removing a habit clears this rather than dropping the row, so subscribed history survives.
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
