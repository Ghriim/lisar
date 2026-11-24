<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** One date of a task entry and where it stands. */
final class TaskPlanningSlotDataOutput
{
    public int $entryId;

    /** The task made for that date; null while it is to come. */
    public ?int $taskId = null;

    /** A calendar day, "YYYY-MM-DD". */
    public string $day;

    /** to_come, open or done. An open task of a day gone by is overdue. */
    public string $state;
}
