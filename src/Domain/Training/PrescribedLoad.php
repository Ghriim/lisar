<?php

declare(strict_types=1);

namespace App\Domain\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

/**
 * The load a prescribed set asks for: its share of the movement's estimated 1RM, rounded down —
 * to the kilogram, or to the half kilogram for a unilateral movement, whose dumbbells come finer.
 * Down, so the load never asks for more than the share says.
 *
 * Nothing to work it out from, nothing worked out: no share — a movement without a load — or no
 * estimated 1RM yet — a movement never done with one.
 */
final readonly class PrescribedLoad
{
    public const float STEP = 1.0;
    public const float UNILATERAL_STEP = 0.5;

    // Below this, a product that lands a hair under a step is a float's doing, not the share's:
    // 70 % of 104.2857… must give 73, not 72.
    private const float TOLERANCE = 1e-9;

    public function of(MovementDataModel $movement, ?float $percentageOfOneRepMax, ?float $estimatedOneRepMax): ?float
    {
        if (null === $percentageOfOneRepMax || null === $estimatedOneRepMax) {
            return null;
        }

        $step = true === $movement->isUnilateral ? self::UNILATERAL_STEP : self::STEP;
        $load = $estimatedOneRepMax * $percentageOfOneRepMax / 100;

        return floor($load / $step + self::TOLERANCE) * $step;
    }
}
