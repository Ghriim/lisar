<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** How much of a workout fell on one muscle. */
final class WorkoutMuscleShareDataOutput
{
    public int $muscleId;

    public string $muscleName;

    /** Sets counted for it: 1 where it is the primary muscle, 0.5 where it is a secondary one. */
    public float $setShare = 0.0;

    /** That count out of every muscle's, 0 to 100, to one decimal. */
    public float $percentage = 0.0;
}
