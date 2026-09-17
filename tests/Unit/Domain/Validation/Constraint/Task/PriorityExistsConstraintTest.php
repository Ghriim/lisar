<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Validation\Constraint\Task\PriorityExistsConstraint;
use PHPUnit\Framework\TestCase;

final class PriorityExistsConstraintTest extends TestCase
{
    public function testItAcceptsNoPriorityAtAll(): void
    {
        self::assertSame([], PriorityExistsConstraint::validate(null, null));
    }

    public function testItAcceptsAPriorityThatWasFound(): void
    {
        self::assertSame([], PriorityExistsConstraint::validate(1, new PriorityDataModel()));
    }

    public function testItRejectsAPriorityThatWasNotFound(): void
    {
        self::assertSame(
            ['priorityId' => [PriorityExistsConstraint::PRIORITY_NOT_FOUND]],
            PriorityExistsConstraint::validate(404, null),
        );
    }
}
