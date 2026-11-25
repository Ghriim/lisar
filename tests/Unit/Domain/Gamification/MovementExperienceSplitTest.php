<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Gamification;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Gamification\MovementExperienceSplit;
use App\Domain\Registry\Gamification\StatRegistry;
use PHPUnit\Framework\TestCase;

/** All to the primary alone; otherwise 70 % to it and 30 % split evenly, secondaries rounded down. */
final class MovementExperienceSplitTest extends TestCase
{
    public function testThePrimaryAloneTakesItAll(): void
    {
        self::assertSame([StatRegistry::STRENGTH => 100], $this->split(StatRegistry::STRENGTH, [], 100));
    }

    public function testOneSecondaryTakesThirtyPercent(): void
    {
        self::assertSame([StatRegistry::STRENGTH => 70, StatRegistry::ENDURANCE => 30], $this->split(StatRegistry::STRENGTH, [StatRegistry::ENDURANCE], 100));
    }

    public function testTwoSecondariesShareThirtyPercent(): void
    {
        self::assertSame(
            [StatRegistry::ENDURANCE => 70, StatRegistry::AGILITY => 15, StatRegistry::STRENGTH => 15],
            $this->split(StatRegistry::ENDURANCE, [StatRegistry::AGILITY, StatRegistry::STRENGTH], 100),
        );
    }

    /** 15 % of 11 is 1.65: each secondary takes 1, and the primary what is left — nothing lost nor made up. */
    public function testRoundingGoesToThePrimary(): void
    {
        $shares = $this->split(StatRegistry::ENDURANCE, [StatRegistry::AGILITY, StatRegistry::STRENGTH], 11);

        self::assertSame([StatRegistry::ENDURANCE => 9, StatRegistry::AGILITY => 1, StatRegistry::STRENGTH => 1], $shares);
        self::assertSame(11, array_sum($shares));
    }

    /** Too little to share: a secondary with nothing is left out. */
    public function testASecondaryWithNothingIsLeftOut(): void
    {
        self::assertSame([StatRegistry::STRENGTH => 2], $this->split(StatRegistry::STRENGTH, [StatRegistry::ENDURANCE, StatRegistry::AGILITY], 2));
    }

    /** @param list<string> $secondaryStats */
    private function split(string $primaryStat, array $secondaryStats, int $experience): array
    {
        $movement = new MovementDataModel();
        $movement->primaryStat = $primaryStat;
        $movement->secondaryStats = $secondaryStats;

        return (new MovementExperienceSplit())->split($movement, $experience);
    }
}
