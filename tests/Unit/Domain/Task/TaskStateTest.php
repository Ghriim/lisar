<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\Registry\Task\TaskStateRegistry;
use App\Domain\Task\TaskState;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TaskStateTest extends TestCase
{
    private TaskState $taskState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskState = new TaskState();
    }

    public function testATaskWithoutSubtasksIsToDo(): void
    {
        self::assertSame(TaskStateRegistry::TO_DO, $this->taskState->resolve(new TaskDataModel()));
    }

    public function testAClosedTaskIsDone(): void
    {
        $task = new TaskDataModel();
        $task->completedAt = new DateTimeImmutable();

        self::assertSame(TaskStateRegistry::DONE, $this->taskState->resolve($task));
    }

    public function testATaskWhoseSubtasksAreAllOpenIsToDo(): void
    {
        $task = $this->buildTaskWithSubtasks(doneCount: 0, openCount: 2);

        self::assertSame(TaskStateRegistry::TO_DO, $this->taskState->resolve($task));
    }

    public function testATaskWithSomeSubtasksDoneIsInProgress(): void
    {
        $task = $this->buildTaskWithSubtasks(doneCount: 1, openCount: 1);

        self::assertSame(TaskStateRegistry::IN_PROGRESS, $this->taskState->resolve($task));
    }

    public function testATaskWhoseSubtasksAreAllDoneIsReadyToClose(): void
    {
        $task = $this->buildTaskWithSubtasks(doneCount: 2, openCount: 0);

        self::assertSame(TaskStateRegistry::READY_TO_CLOSE, $this->taskState->resolve($task));
    }

    /**
     * The middle states describe progress through subtasks, and a subtask has none.
     */
    public function testASubtaskOnlyEverReadsAsToDoOrDone(): void
    {
        $subtask = new TaskDataModel();
        $subtask->parent = new TaskDataModel();

        self::assertSame(TaskStateRegistry::TO_DO, $this->taskState->resolve($subtask));

        $subtask->completedAt = new DateTimeImmutable();
        self::assertSame(TaskStateRegistry::DONE, $this->taskState->resolve($subtask));
    }

    private function buildTaskWithSubtasks(int $doneCount, int $openCount): TaskDataModel
    {
        $task = new TaskDataModel();

        for ($i = 0; $i < $doneCount; ++$i) {
            $subtask = new TaskDataModel();
            $subtask->parent = $task;
            $subtask->completedAt = new DateTimeImmutable();
            $task->subtasks->add($subtask);
        }

        for ($i = 0; $i < $openCount; ++$i) {
            $subtask = new TaskDataModel();
            $subtask->parent = $task;
            $task->subtasks->add($subtask);
        }

        return $task;
    }
}
