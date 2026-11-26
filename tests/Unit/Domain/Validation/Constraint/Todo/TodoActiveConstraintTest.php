<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class TodoActiveConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsAnActiveTodo(): void
    {
        self::assertSame([], TodoActiveConstraint::validate($this->todoOf($this->anyCurrentUser())));
    }

    public function testItRejectsAnArchivedTodoOnTheTodoItself(): void
    {
        self::assertSame(
            ['id' => [TodoActiveConstraint::TODO_ARCHIVED]],
            TodoActiveConstraint::validate($this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED)),
        );
    }

    public function testItSaysItWhereItIsAsked(): void
    {
        self::assertSame(
            ['todoId' => [TodoActiveConstraint::TODO_ARCHIVED]],
            TodoActiveConstraint::validate($this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED), 'todoId'),
        );
    }
}
