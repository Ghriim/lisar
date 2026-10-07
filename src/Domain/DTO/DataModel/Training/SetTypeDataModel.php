<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * What kind of set a logged set was — a warm-up, a dropset, one taken to failure. Reference data
 * an administrator maintains. A set carries one or none: a set without a type is an ordinary
 * working set, so there is no "normal" row.
 *
 * The name is unique ignoring case: the column's collation compares case-insensitively, so
 * "Dropset" and "dropset" are the same name to the index and to every lookup.
 */
#[ORM\Table(name: 'set_type')]
#[ORM\UniqueConstraint(name: 'set_type_name', columns: ['name'])]
#[ORM\Entity]
class SetTypeDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 128)]
    public string $name;

    // One of SetTypeColourRegistry's codes; each front end paints its own shade for it.
    #[ORM\Column(length: 32)]
    public string $colour;

    // Inactive: no longer offered to new sets; the sets already carrying it keep it.
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
