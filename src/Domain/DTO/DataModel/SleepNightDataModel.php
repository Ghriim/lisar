<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One person's night. At most one per day, by constraint.
 *
 * The day is **the one they woke up on**: a night crosses midnight, so it has to be attached to
 * one of the two days it touches, and the waking day is the one a person means by "the night of
 * the 18th".
 *
 * The two moments are stored as instants, not as times of day, because that is the only form in
 * which they are unambiguous. The duration is not stored: it is computed from them by
 * Domain\Tracking\SleepWindow, and a stored duration would be a second version of the truth
 * waiting to disagree with the first.
 */
#[ORM\Table(name: 'sleep_night')]
#[ORM\UniqueConstraint(name: 'sleep_night_owner_day', columns: ['owner_id', 'day'])]
#[ORM\Entity]
class SleepNightDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // The waking day: a calendar day in the application's timezone, with no time of day.
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public DateTimeImmutable $day;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $bedtimeAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $wakeUpAt;

    // How one felt on waking, 1 to 5. Null because rating oneself is optional: a tracker that
    // refuses to record until it has been given a feeling is one people stop opening.
    #[ORM\Column(nullable: true)]
    public ?int $moodRating = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
