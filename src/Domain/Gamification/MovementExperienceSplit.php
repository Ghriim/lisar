<?php

declare(strict_types=1);

namespace App\Domain\Gamification;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

/**
 * How the physical experience a movement earns is shared between its stats. The primary alone
 * takes it all; with secondaries, the primary takes 70 % and the other 30 % is split evenly
 * between them. Experience is a whole number: a secondary's share is rounded down, and what
 * rounding leaves goes to the primary, so that nothing is lost nor made up.
 */
final readonly class MovementExperienceSplit
{
    private const int PRIMARY_PERCENT = 70;

    /** @return array<string, int> stat => experience, the primary first; a stat with nothing is left out */
    public function split(MovementDataModel $movement, int $experience): array
    {
        $secondaries = array_values(array_diff($movement->secondaryStats, [$movement->primaryStat]));
        if ([] === $secondaries) {
            return [$movement->primaryStat => $experience];
        }

        $eachSecondary = intdiv($experience * (100 - self::PRIMARY_PERCENT), 100 * count($secondaries));
        $shares = [$movement->primaryStat => $experience - $eachSecondary * count($secondaries)];
        foreach ($secondaries as $secondary) {
            $shares[$secondary] = $eachSecondary;
        }

        return array_filter($shares, static fn (int $share): bool => $share > 0);
    }
}
