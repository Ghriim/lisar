<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One thing drunk, at a moment.
 *
 * The volume is a copy, never a reference to the shortcut that produced it: correcting a
 * shortcut from 250 to 200 mL changes what the next tap logs, and nothing about what was drunk
 * yesterday.
 */
#[ORM\Table(name: 'hydration_entry')]
#[ORM\Entity]
class HydrationEntryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HydrationDayDataModel::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'hydration_day_id', nullable: false, onDelete: 'CASCADE')]
    public HydrationDayDataModel $hydrationDay;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $recordedAt;

    #[ORM\Column]
    public int $volumeInMillilitres;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
