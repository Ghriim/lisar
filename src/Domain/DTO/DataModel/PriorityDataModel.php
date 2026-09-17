<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A priority level, managed in the back-office only. It has no functional effect beyond display
 * and sorting.
 */
#[ORM\Table(name: 'task_priority')]
#[ORM\Entity]
class PriorityDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    public string $label;

    // Gives the sort order inside a category. Lower sorts first.
    #[ORM\Column]
    public int $weight = 0;

    /** Hex colour the front ends render, "#RRGGBB". */
    #[ORM\Column(length: 7)]
    public string $colour;

    // Exactly one priority carries this, and it applies to tasks created without one.
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isDefault = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
