<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\Validation\Constraint\Todo\TodoTitleAvailableConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class TodoTitleAvailableConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsATitleNoTodoHas(): void
    {
        self::assertSame([], TodoTitleAvailableConstraint::validate(null));
    }

    public function testItRejectsATitleAnotherTodoHas(): void
    {
        self::assertSame(
            ['title' => [TodoTitleAvailableConstraint::TITLE_ALREADY_USED]],
            TodoTitleAvailableConstraint::validate($this->todoOf($this->anyCurrentUser(), 3)),
        );
    }

    /** Renamed, a TODO may keep the title it already has. */
    public function testItLetsTheTodoBeingRenamedKeepItsOwnTitle(): void
    {
        self::assertSame([], TodoTitleAvailableConstraint::validate($this->todoOf($this->anyCurrentUser(), 3), 3));
    }

    public function testItRejectsTheTitleOfAnotherTodoOnARename(): void
    {
        self::assertSame(
            ['title' => [TodoTitleAvailableConstraint::TITLE_ALREADY_USED]],
            TodoTitleAvailableConstraint::validate($this->todoOf($this->anyCurrentUser(), 3), 4),
        );
    }
}
