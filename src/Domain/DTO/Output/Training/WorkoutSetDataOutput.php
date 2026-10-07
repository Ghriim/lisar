<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use Symfony\Component\ObjectMapper\Attribute\Map;

/** One set: the measures its movement tracks, null for the others. */
final class WorkoutSetDataOutput
{
    public int $id;

    /** Null for an ordinary working set. */
    #[Map(if: false)]
    public ?SetTypeDataOutput $setType = null;

    public ?int $reps = null;

    public ?float $weightInKilograms = null;

    public ?int $durationInSeconds = null;

    public ?int $distanceInMetres = null;

    public ?float $rpe = null;

    /** Ticked as done. Always true in a finished workout. */
    public bool $isComplete = false;
}
