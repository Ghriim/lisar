<?php

declare(strict_types=1);

namespace App\Domain\Registry\Task;

/**
 * The four states a task can be read in. Only DONE is chosen by the person; the two middle ones
 * are derived from how far its subtasks have got, and none of them is stored.
 */
interface TaskStateRegistry
{
    public const string TO_DO = 'to_do';
    public const string IN_PROGRESS = 'in_progress';
    public const string READY_TO_CLOSE = 'ready_to_close';
    public const string DONE = 'done';
}
