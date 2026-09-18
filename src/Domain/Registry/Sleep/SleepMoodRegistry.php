<?php

declare(strict_types=1);

namespace App\Domain\Registry\Sleep;

/**
 * How one felt on waking, from 1 to 5.
 *
 * A scale, not reference data: it will not move, so it lives here rather than in a table an
 * administrator maintains. The API answers the number and nothing else — each front end draws
 * its own face for it, and words it in its own language.
 */
interface SleepMoodRegistry
{
    /** A bad morning. */
    public const int WORST = 1;

    /** A good one. */
    public const int BEST = 5;
}
