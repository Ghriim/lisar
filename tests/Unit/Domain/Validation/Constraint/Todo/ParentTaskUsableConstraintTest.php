<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\Validation\Constraint\Todo\ParentTaskUsableConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ParentTaskUsableConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsATaskWithoutAParent(): void
    {
        self::assertSame([], ParentTaskUsableConstraint::validate(null, null, $this->currentUser(1)));
    }

    public function testItAcceptsARootTaskAsAParent(): void
    {
        self::assertSame([], ParentTaskUsableConstraint::validate(1, $this->taskOf($this->currentUser(1)), $this->currentUser(1)));
    }

    public function testItRejectsAParentThatWasNotFound(): void
    {
        self::assertSame(
            ['parentId' => [ParentTaskUsableConstraint::PARENT_TASK_NOT_FOUND]],
            ParentTaskUsableConstraint::validate(404, null, $this->currentUser(1)),
        );
    }

    /** Someone else's task is answered like one that does not exist. */
    public function testItRejectsSomeoneElsesTaskAsNotFound(): void
    {
        self::assertSame(
            ['parentId' => [ParentTaskUsableConstraint::PARENT_TASK_NOT_FOUND]],
            ParentTaskUsableConstraint::validate(1, $this->taskOf($this->currentUser(2)), $this->currentUser(1)),
        );
    }

    public function testItRejectsASubtaskAsAParent(): void
    {
        $subtask = $this->taskOf($this->currentUser(1));
        $subtask->parent = $this->taskOf($this->currentUser(1));

        self::assertSame(
            ['parentId' => [ParentTaskUsableConstraint::PARENT_TASK_IS_A_SUBTASK]],
            ParentTaskUsableConstraint::validate(1, $subtask, $this->currentUser(1)),
        );
    }
}
