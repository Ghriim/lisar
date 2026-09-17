<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\Validation\Constraint\Task\CategoryUnusedConstraint;
use PHPUnit\Framework\TestCase;

final class CategoryUnusedConstraintTest extends TestCase
{
    public function testAnEmptyCategoryMayGo(): void
    {
        self::assertSame([], CategoryUnusedConstraint::validate(0));
    }

    public function testACategoryWithASingleTaskMayNot(): void
    {
        self::assertSame(
            ['id' => [CategoryUnusedConstraint::CATEGORY_IN_USE]],
            CategoryUnusedConstraint::validate(1),
        );
    }
}
