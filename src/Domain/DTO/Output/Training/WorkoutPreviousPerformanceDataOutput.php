<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/**
 * What a movement gave the last time it was done: the sets of the latest finished workout before
 * this one in which it came. When it came twice there, the sets of both, in order.
 */
final class WorkoutPreviousPerformanceDataOutput
{
    public int $movementId;

    public int $workoutId;

    public string $startedAt;

    /** @var list<WorkoutSetDataOutput> */
    public array $sets = [];
}
