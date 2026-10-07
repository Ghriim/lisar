<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A piece of equipment a movement is done with — a barbell, a jump rope, a leg press machine.
 * Reference data an administrator maintains.
 *
 * The two flags tell a movement what to track when it is logged: `hasWeight` a load, `hasDistance`
 * a distance. A movement with no equipment at all is done with the body's own weight; there is no
 * "bodyweight" equipment.
 *
 * The name is unique ignoring case: the column's collation compares case-insensitively, so
 * "Barbell" and "barbell" are the same name to the index and to every lookup.
 */
#[ORM\Table(name: 'equipment')]
#[ORM\UniqueConstraint(name: 'equipment_name', columns: ['name'])]
#[ORM\Entity]
class EquipmentDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 128)]
    public string $name;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $hasWeight = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $hasDistance = false;

    // Inactive: no longer offered to new movements; the movements already using it keep it.
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
