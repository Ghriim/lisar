<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\Validation\Constraint\Task\ParentTaskUsableConstraint;
use PHPUnit\Framework\TestCase;

final class ParentTaskUsableConstraintTest extends TestCase
{
    public function testItAcceptsATaskWithoutAParent(): void
    {
        self::assertSame([], ParentTaskUsableConstraint::validate(null, null));
    }

    public function testItAcceptsARootTaskAsAParent(): void
    {
        self::assertSame([], ParentTaskUsableConstraint::validate(1, new TaskDataModel()));
    }

    public function testItRejectsAParentThatWasNotFound(): void
    {
        self::assertSame(
            ['parentId' => [ParentTaskUsableConstraint::PARENT_TASK_NOT_FOUND]],
            ParentTaskUsableConstraint::validate(404, null),
        );
    }

    public function testItRejectsASubtaskAsAParent(): void
    {
        $subtask = new TaskDataModel();
        $subtask->parent = new TaskDataModel();

        self::assertSame(
            ['parentId' => [ParentTaskUsableConstraint::PARENT_TASK_IS_A_SUBTASK]],
            ParentTaskUsableConstraint::validate(1, $subtask),
        );
    }
}
