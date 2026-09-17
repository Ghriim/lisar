<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One thing to do. A subtask is the very same shape with a parent set: it carries its own due
 * date, priority, category and tags, and cannot have subtasks of its own.
 */
#[ORM\Table(name: 'task')]
#[ORM\Entity]
class TaskDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    #[ORM\Column(length: 255)]
    public string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    // A calendar day, with no time of day: "do it on" is the scheduling component's business.
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $dueDate = null;

    // RESTRICT on both: a priority or a category still in use cannot be deleted, and the
    // database is what makes that true even if a use case forgets to ask.
    #[ORM\ManyToOne(targetEntity: PriorityDataModel::class)]
    #[ORM\JoinColumn(name: 'priority_id', nullable: true, onDelete: 'RESTRICT')]
    public ?PriorityDataModel $priority = null;

    #[ORM\ManyToOne(targetEntity: CategoryDataModel::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'RESTRICT')]
    public ?CategoryDataModel $category = null;

    /** @var Collection<int, TagDataModel> */
    #[ORM\ManyToMany(targetEntity: TagDataModel::class)]
    #[ORM\JoinTable(name: 'task_tag_assignment')]
    #[ORM\JoinColumn(name: 'task_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'task_tag_id', onDelete: 'CASCADE')]
    public Collection $tags;

    // Set only on a subtask. Deleting a parent deletes its subtasks.
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subtasks')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'CASCADE')]
    public ?TaskDataModel $parent = null;

    /** @var Collection<int, TaskDataModel> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    public Collection $subtasks;

    // The single source of truth for "done": a task is done when it was closed, by hand.
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->subtasks = new ArrayCollection();
    }

    public function isDone(): bool
    {
        return null !== $this->completedAt;
    }

    public function isSubtask(): bool
    {
        return null !== $this->parent;
    }

    /** @return list<TaskDataModel> */
    public function getOpenSubtasks(): array
    {
        $open = [];
        foreach ($this->subtasks as $subtask) {
            if (false === $subtask->isDone()) {
                $open[] = $subtask;
            }
        }

        return $open;
    }
}
