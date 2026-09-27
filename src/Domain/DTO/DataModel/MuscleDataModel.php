<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A muscle a movement can target, in exactly one group. Its name is unique across every group, not
 * only within its own: a movement lists muscles by name, and two "Upper chest" would read as one.
 *
 * A muscle is offered to new movements only when it is active **and** its group is.
 */
#[ORM\Table(name: 'muscle')]
#[ORM\UniqueConstraint(name: 'muscle_name', columns: ['name'])]
#[ORM\Entity]
class MuscleDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    // Unique ignoring case, through the column's collation — see EquipmentDataModel.
    #[ORM\Column(length: 128)]
    public string $name;

    // RESTRICT: a group is deleted only once it is empty, and the database holds that line too.
    #[ORM\ManyToOne(targetEntity: MuscleGroupDataModel::class)]
    #[ORM\JoinColumn(name: 'muscle_group_id', nullable: false, onDelete: 'RESTRICT')]
    public MuscleGroupDataModel $muscleGroup;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
