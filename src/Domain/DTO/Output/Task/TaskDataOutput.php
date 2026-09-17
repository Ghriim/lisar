<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Task;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class TaskDataOutput
{
    public int $id;

    public string $title;

    public ?string $description = null;

    /** A calendar day, "YYYY-MM-DD", with no time of day. */
    #[Map(transform: [DateDataTransformer::class, 'dateToDayString'])]
    public ?string $dueDate = null;

    /** One of TaskStateRegistry. Derived, never stored. */
    #[Map(if: false)]
    public string $state;

    #[Map(if: false)]
    public ?PriorityDataOutput $priority = null;

    #[Map(if: false)]
    public ?CategoryDataOutput $category = null;

    /**
     * The labels, not the rows: a tag is a word to the person using it.
     *
     * @var list<string>
     */
    #[Map(if: false)]
    public array $tags = [];

    /**
     * Always empty on a subtask — there is only ever one level.
     *
     * @var list<TaskDataOutput>
     */
    #[Map(if: false)]
    public array $subtasks = [];

    // Assigned by the OutputFactory, not mapped: a dotted source cannot walk through a null
    // parent, and a root task has none.
    #[Map(if: false)]
    public ?int $parentId = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $completedAt = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $createdAt = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $updatedAt = null;
}
