<?php

declare(strict_types=1);

namespace App\Domain\Registry\Training;

/**
 * The fixed tiers of the tiered personal bests. A load is a tier too, for the distance it was
 * carried, but its tiers are the loads logged, not a list.
 */
interface PersonalBestTierRegistry
{
    /** For MAX_WEIGHT_FOR_REPS: the 1RM, 3RM, 5RM… */
    public const array REPS = [1, 3, 5, 8, 10, 12];

    /** For BEST_TIME_FOR_DISTANCE, in metres: 1 km to 150 km, the half and the marathon included. */
    public const array DISTANCES_IN_METRES = [1000, 5000, 10000, 21097, 42195, 50000, 100000, 150000];
}
