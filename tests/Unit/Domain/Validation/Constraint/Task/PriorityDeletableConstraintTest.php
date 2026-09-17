<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Validation\Constraint\Task\PriorityDeletableConstraint;
use PHPUnit\Framework\TestCase;

final class PriorityDeletableConstraintTest extends TestCase
{
    public function testAnUnusedNonDefaultPriorityMayGo(): void
    {
        self::assertSame([], PriorityDeletableConstraint::validate($this->buildPriority(false), 0));
    }

    public function testAPriorityTasksCarryMayNot(): void
    {
        self::assertSame(
            ['id' => [PriorityDeletableConstraint::PRIORITY_IN_USE]],
            PriorityDeletableConstraint::validate($this->buildPriority(false), 3),
        );
    }

    public function testTheDefaultMayNot(): void
    {
        self::assertSame(
            ['id' => [PriorityDeletableConstraint::PRIORITY_IS_THE_DEFAULT]],
            PriorityDeletableConstraint::validate($this->buildPriority(true), 0),
        );
    }

    public function testItReportsBothReasonsAtOnce(): void
    {
        $violations = PriorityDeletableConstraint::validate($this->buildPriority(true), 3);

        self::assertCount(2, $violations['id']);
    }

    private function buildPriority(bool $isDefault): PriorityDataModel
    {
        $priority = new PriorityDataModel();
        $priority->isDefault = $isDefault;

        return $priority;
    }
}
