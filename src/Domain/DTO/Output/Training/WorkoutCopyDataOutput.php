<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/**
 * A workout started from a past one: the new workout, whole, and what it could not take over.
 */
final class WorkoutCopyDataOutput
{
    public WorkoutDataOutput $workout;

    /**
     * The names of the movements left out because they are no longer offered, each once.
     *
     * @var list<string>
     */
    public array $skippedMovements = [];
}
