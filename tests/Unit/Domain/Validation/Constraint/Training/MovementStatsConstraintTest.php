<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Registry\Gamification\StatRegistry;
use App\Domain\Validation\Constraint\Training\MovementStatsConstraint;
use PHPUnit\Framework\TestCase;

final class MovementStatsConstraintTest extends TestCase
{
    public function testDistinctStatsPass(): void
    {
        self::assertSame([], MovementStatsConstraint::validate(StatRegistry::STRENGTH, [StatRegistry::ENDURANCE, StatRegistry::AGILITY]));
        self::assertSame([], MovementStatsConstraint::validate(StatRegistry::STRENGTH, []));
    }

    public function testThePrimaryAmongTheSecondariesIsRefused(): void
    {
        self::assertSame(
            ['secondaryStats' => [MovementStatsConstraint::PRIMARY_STAT_ALSO_SECONDARY]],
            MovementStatsConstraint::validate(StatRegistry::AGILITY, [StatRegistry::AGILITY]),
        );
    }

    /** A blank primary is the input's own violation: not said twice. */
    public function testABlankPrimaryIsLeftToTheInput(): void
    {
        self::assertSame([], MovementStatsConstraint::validate('', ['']));
    }
}
