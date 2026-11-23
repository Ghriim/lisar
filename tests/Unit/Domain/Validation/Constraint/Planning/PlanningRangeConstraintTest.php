<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Planning;

use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use PHPUnit\Framework\TestCase;

final class PlanningRangeConstraintTest extends TestCase
{
    public function testAWeekAndSixWeeksAreAccepted(): void
    {
        self::assertSame([], PlanningRangeConstraint::validate('2026-11-23', '2026-11-29'));
        self::assertSame([], PlanningRangeConstraint::validate('2026-11-23', '2026-11-23'));
        self::assertSame([], PlanningRangeConstraint::validate('2026-11-02', '2026-12-13'));
    }

    public function testARangeEndingBeforeItStartsIsRefused(): void
    {
        self::assertSame(['to' => [PlanningRangeConstraint::RANGE_INVALID]], PlanningRangeConstraint::validate('2026-11-29', '2026-11-23'));
    }

    public function testARangePastSixWeeksIsRefused(): void
    {
        self::assertSame(['to' => [PlanningRangeConstraint::RANGE_INVALID]], PlanningRangeConstraint::validate('2026-11-02', '2026-12-14'));
    }

    /** Malformed days are the input's to refuse: nothing more is said here. */
    public function testMalformedDaysAddNothing(): void
    {
        self::assertSame(['from' => ['from_invalid']], PlanningRangeConstraint::validate('hier', '2026-11-29', ['from' => ['from_invalid']]));
    }
}
