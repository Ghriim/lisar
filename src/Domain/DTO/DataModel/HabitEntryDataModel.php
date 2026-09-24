<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Whether a subscribed habit was kept on a given day. One row per subscription per day, by
 * constraint — the same shape as a tracker's day.
 *
 * `isCompleted` is the frozen outcome: for a manual habit, the person ticked it; for a tracker
 * habit, the tracker's figure had reached the mark when it was last evaluated. It can read false
 * on an existing row — a tracker figure corrected back below its mark unkeeps the day. `source`
 * says who set it (HabitSourceRegistry::MANUAL, or a HabitTrackerRegistry value), and `completedAt`
 * is the instant it was kept — the hook the experience domain to come will reward, null while the
 * day is not kept.
 */
#[ORM\Table(name: 'habit_entry')]
#[ORM\UniqueConstraint(name: 'habit_entry_subscription_day', columns: ['subscription_id', 'day'])]
#[ORM\Entity]
class HabitEntryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HabitSubscriptionDataModel::class)]
    #[ORM\JoinColumn(name: 'subscription_id', nullable: false, onDelete: 'CASCADE')]
    public HabitSubscriptionDataModel $subscription;

    // A calendar day in the application's timezone, with no time of day.
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public DateTimeImmutable $day;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isCompleted = false;

    // Who kept it: HabitSourceRegistry::MANUAL, or a HabitTrackerRegistry value.
    #[ORM\Column(length: 32)]
    public string $source;

    // The instant it was kept, on the clock days are counted on; null while the day is not kept.
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
