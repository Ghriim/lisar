<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * What the variants of one movement have in common: "Bench press" gathers the barbell and the
 * dumbbell bench presses, so a progression can later be followed across both.
 *
 * Every movement sits in exactly one family, even alone in it. Deactivating a family withdraws its
 * movements from what is offered without touching their own flag — the same rule as a muscle
 * group.
 */
#[ORM\Table(name: 'movement_family')]
#[ORM\UniqueConstraint(name: 'movement_family_name', columns: ['name'])]
#[ORM\Entity]
class MovementFamilyDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    // Unique ignoring case, through the column's collation — see EquipmentDataModel.
    #[ORM\Column(length: 128)]
    public string $name;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
