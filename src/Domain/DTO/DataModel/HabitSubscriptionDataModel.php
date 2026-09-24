<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One person taking a habit on. It is their copy of a catalogue habit — the row that makes the
 * habit theirs, and the thing a day's entry hangs off.
 *
 * Unsubscribing clears `isActive` rather than dropping the row: the days already kept must not be
 * erased, so a dropped-then-resumed habit keeps its past. At most one subscription per person per
 * habit, by constraint.
 */
#[ORM\Table(name: 'habit_subscription')]
#[ORM\UniqueConstraint(name: 'habit_subscription_owner_habit', columns: ['owner_id', 'habit_id'])]
#[ORM\Entity]
class HabitSubscriptionDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    #[ORM\ManyToOne(targetEntity: HabitDataModel::class)]
    #[ORM\JoinColumn(name: 'habit_id', nullable: false, onDelete: 'CASCADE')]
    public HabitDataModel $habit;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
