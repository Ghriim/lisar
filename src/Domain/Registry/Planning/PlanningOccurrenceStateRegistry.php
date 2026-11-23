<?php

declare(strict_types=1);

namespace App\Domain\Registry\Planning;

/** Where one date of a planning entry stands. Worked out on reading, never stored. */
interface PlanningOccurrenceStateRegistry
{
    /** Today or later, and not kept yet. */
    public const string TO_COME = 'to_come';

    public const string KEPT = 'kept';

    /** A past day that went by without being kept. */
    public const string MISSED = 'missed';
}
