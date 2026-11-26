<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Constraint\Todo\TodoUsableConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class TodoUsableConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsAnActiveTodoOfTheOwner(): void
    {
        self::assertSame([], TodoUsableConstraint::validate($this->todoOf($this->currentUser(1)), $this->currentUser(1)));
    }

    public function testItRejectsATodoThatWasNotFound(): void
    {
        self::assertSame(['todoId' => [TodoUsableConstraint::TODO_NOT_FOUND]], TodoUsableConstraint::validate(null, $this->currentUser(1)));
    }

    /** Someone else's TODO is answered like one that does not exist. */
    public function testItRejectsSomeoneElsesTodoAsNotFound(): void
    {
        self::assertSame(
            ['todoId' => [TodoUsableConstraint::TODO_NOT_FOUND]],
            TodoUsableConstraint::validate($this->todoOf($this->currentUser(2)), $this->currentUser(1)),
        );
    }

    public function testItRejectsAnArchivedTodo(): void
    {
        self::assertSame(
            ['todoId' => [TodoActiveConstraint::TODO_ARCHIVED]],
            TodoUsableConstraint::validate($this->todoOf($this->currentUser(1), status: TodoStatusRegistry::ARCHIVED), $this->currentUser(1)),
        );
    }
}
