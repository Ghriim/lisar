<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\Validation\Constraint\Todo\TaskFavoritableConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class TaskFavoritableConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsARootTask(): void
    {
        self::assertSame([], TaskFavoritableConstraint::validate($this->taskOf($this->anyCurrentUser())));
    }

    public function testItRejectsASubtask(): void
    {
        $subtask = $this->taskOf($this->anyCurrentUser());
        $subtask->parent = $this->taskOf($this->anyCurrentUser());

        self::assertSame(['id' => [TaskFavoritableConstraint::SUBTASK_NOT_FAVORITABLE]], TaskFavoritableConstraint::validate($subtask));
    }
}
