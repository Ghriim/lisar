<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\Validation\Constraint\Task\DefaultPriorityKeptConstraint;
use PHPUnit\Framework\TestCase;

final class DefaultPriorityKeptConstraintTest extends TestCase
{
    public function testAPriorityThatIsNotTheDefaultIsFreeToStayThatWay(): void
    {
        self::assertSame([], DefaultPriorityKeptConstraint::validate($this->buildPriority(false), false));
    }

    public function testAnyPriorityMayBecomeTheDefault(): void
    {
        self::assertSame([], DefaultPriorityKeptConstraint::validate($this->buildPriority(false), true));
    }

    public function testTheDefaultMayStayTheDefault(): void
    {
        self::assertSame([], DefaultPriorityKeptConstraint::validate($this->buildPriority(true), true));
    }

    /**
     * The default is never dropped, only handed over: a task created without a priority has to
     * get something.
     */
    public function testTheDefaultCannotSimplyBeUnset(): void
    {
        self::assertSame(
            ['isDefault' => [DefaultPriorityKeptConstraint::DEFAULT_PRIORITY_REQUIRED]],
            DefaultPriorityKeptConstraint::validate($this->buildPriority(true), false),
        );
    }

    private function buildPriority(bool $isDefault): PriorityDataModel
    {
        $priority = new PriorityDataModel();
        $priority->isDefault = $isDefault;

        return $priority;
    }
}
