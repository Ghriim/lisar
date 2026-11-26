<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\Validation\Constraint\Todo\TodoNotDefaultConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class TodoNotDefaultConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsAnOrdinaryTodo(): void
    {
        self::assertSame([], TodoNotDefaultConstraint::validate($this->todoOf($this->anyCurrentUser())));
    }

    public function testItRejectsTheDefaultTodo(): void
    {
        self::assertSame(
            ['id' => [TodoNotDefaultConstraint::TODO_IS_DEFAULT]],
            TodoNotDefaultConstraint::validate($this->todoOf($this->anyCurrentUser(), isDefault: true)),
        );
    }
}
