<?php

declare(strict_types=1);

namespace App\Domain\Gamification;

/**
 * How experience turns into a level — the same curve for every stat and for the global level.
 * Everyone starts at level 1 with 0 XP; going from level N to level N + 1 takes 50 × N²: 50 for
 * level 2, 200 more for level 3, 14 250 in all for level 10.
 */
final readonly class LevelCurve
{
    private const int STEP = 50;

    public function levelFor(int $experience): int
    {
        $level = 1;
        while ($this->experienceToReach($level + 1) <= $experience) {
            ++$level;
        }

        return $level;
    }

    /** All the experience a level takes, from 0: 0 for level 1, 50 for level 2, 250 for level 3. */
    public function experienceToReach(int $level): int
    {
        $below = max(0, $level - 1);

        // 50 × (1² + 2² + … + (L - 1)²)
        return intdiv(self::STEP * $below * ($below + 1) * (2 * $below + 1), 6);
    }

    /** What going from `$level` to the next takes. */
    public function experienceForNext(int $level): int
    {
        return self::STEP * $level * $level;
    }
}
