<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\Validation\Constraint\Task\TaskClosableConstraint;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TaskClosableConstraintTest extends TestCase
{
    public function testATaskWithoutSubtasksIsClosable(): void
    {
        self::assertSame([], TaskClosableConstraint::validate(new TaskDataModel()));
    }

    public function testATaskWhoseSubtasksAreAllDoneIsClosable(): void
    {
        $task = new TaskDataModel();
        $task->subtasks->add($this->buildSubtask($task, isDone: true));

        self::assertSame([], TaskClosableConstraint::validate($task));
    }

    public function testATaskWithAnOpenSubtaskIsNotClosable(): void
    {
        $task = new TaskDataModel();
        $task->subtasks->add($this->buildSubtask($task, isDone: true));
        $task->subtasks->add($this->buildSubtask($task, isDone: false));

        self::assertSame(
            ['id' => [TaskClosableConstraint::TASK_HAS_OPEN_SUBTASKS]],
            TaskClosableConstraint::validate($task),
        );
    }

    private function buildSubtask(TaskDataModel $parent, bool $isDone): TaskDataModel
    {
        $subtask = new TaskDataModel();
        $subtask->parent = $parent;
        $subtask->completedAt = true === $isDone ? new DateTimeImmutable() : null;

        return $subtask;
    }
}
