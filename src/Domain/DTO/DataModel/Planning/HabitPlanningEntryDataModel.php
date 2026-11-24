<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Planning;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The days a subscribed habit counts, on a rule of dates. It belongs to the person's subscription,
 * not to the catalogue: a subscription without any entry counts every day; once it has some, its
 * entries say which days instead. Nothing is written for a date ahead of time — whether it was
 * kept is read from the habit's own days.
 *
 * The schedule fields are the same on every kind of planning entry, and read by PlanningRule:
 * each rule reads its own and the others stay empty.
 */
#[ORM\Table(name: 'habit_planning_entry')]
#[ORM\Index(name: 'habit_planning_entry_owner', columns: ['owner_id'])]
#[ORM\Entity]
class HabitPlanningEntryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // Unsubscribing deletes the entries outright (the subscription row itself stays, deactivated).
    #[ORM\ManyToOne(targetEntity: HabitSubscriptionDataModel::class)]
    #[ORM\JoinColumn(name: 'subscription_id', nullable: false, onDelete: 'CASCADE')]
    public HabitSubscriptionDataModel $subscription;

    /** One of PlanningRuleRegistry. */
    #[ORM\Column(length: 32)]
    public string $rule;

    // Calendar days, with no time of day.
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $endDate = null;

    /** @var list<int> ISO weekdays, 1 for Monday */
    #[ORM\Column(type: Types::JSON)]
    public array $weekdays = [];

    #[ORM\Column]
    public int $weekInterval = 1;

    /** @var list<int> 1 to 5, or PlanningRuleRegistry::LAST_WEEK */
    #[ORM\Column(type: Types::JSON)]
    public array $monthWeeks = [];

    /** @var list<int> 1 to 31 */
    #[ORM\Column(type: Types::JSON)]
    public array $monthDays = [];

    // Minutes since midnight. None: the entry holds within the day.
    #[ORM\Column(nullable: true)]
    public ?int $startTimeInMinutes = null;

    // Only with a start time.
    #[ORM\Column(nullable: true)]
    public ?int $durationInMinutes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
