<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A family of muscles — chest, back, legs. It exists to group the muscles a movement targets, for
 * the back-office and, later, for statistics by group.
 *
 * Deactivating a group withdraws every muscle in it from new movements without touching their own
 * flag, so reactivating the group gives them back exactly as they were.
 */
#[ORM\Table(name: 'muscle_group')]
#[ORM\UniqueConstraint(name: 'muscle_group_name', columns: ['name'])]
#[ORM\Entity]
class MuscleGroupDataModel implements DataModelInterface
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
