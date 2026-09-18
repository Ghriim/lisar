<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One person's weight on one day. At most one row per day, by constraint: weighing yourself
 * twice is the same measurement taken twice, and the second one is the one that counts.
 *
 * Two temporal columns, which are not the same thing. `day` is what the measurement belongs to
 * and what the uniqueness is counted on; `recordedAt` is the instant the person stepped on the
 * scale, and a morning weight is not comparable to an evening one. Correcting the value never
 * moves `recordedAt` — fixing a typo at noon does not mean they weighed themselves at noon.
 */
#[ORM\Table(name: 'weight_entry')]
#[ORM\UniqueConstraint(name: 'weight_entry_owner_day', columns: ['owner_id', 'day'])]
#[ORM\Entity]
class WeightEntryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // A calendar day in the application's timezone, with no time of day.
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public DateTimeImmutable $day;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $recordedAt;

    // DECIMAL(5,2): up to 999.99 kg, two decimals kept exactly. A float column would drift, and
    // averaging a drifted series is how a tracker starts disagreeing with the scale.
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    public float $weightInKilograms;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
