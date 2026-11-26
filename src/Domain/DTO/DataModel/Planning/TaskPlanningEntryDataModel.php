<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Planning;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A task model planned on a rule of dates: each date gets its own task, made from the model —
 * the day it falls on for a recurring rule, at once for a single date. The tasks made are
 * ordinary tasks, linked back to the entry and their date (TaskDataModel::$planningEntry).
 *
 * The schedule fields are the same on every kind of planning entry, and read by PlanningRule:
 * each rule reads its own and the others stay empty.
 */
#[ORM\Table(name: 'task_planning_entry')]
#[ORM\Index(name: 'task_planning_entry_owner', columns: ['owner_id'])]
#[ORM\Entity]
class TaskPlanningEntryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // The TODO its tasks are made in. Archiving the TODO deletes the entry, deleting it too.
    #[ORM\ManyToOne(targetEntity: TodoDataModel::class)]
    #[ORM\JoinColumn(name: 'todo_id', nullable: false, onDelete: 'CASCADE')]
    public TodoDataModel $todo;

    // The model each task is made from: title, description, category. Subtasks are not part of it.
    #[ORM\Column(length: 255)]
    public string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    // SET NULL, unlike a task's RESTRICT: a category is refused deletion while tasks use it, and
    // a model that loses its category simply makes its next tasks without one.
    #[ORM\ManyToOne(targetEntity: CategoryDataModel::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    public ?CategoryDataModel $category = null;

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

    // Minutes since midnight. None: the entry holds within the day. It places the date on the
    // planning; the task made carries no time of day.
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
