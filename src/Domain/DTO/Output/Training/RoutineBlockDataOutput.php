<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

/** One step of a routine; more than one exercise makes it a superset. */
final class RoutineBlockDataOutput
{
    public int $id;

    /** @var list<RoutineExerciseDataOutput> */
    public array $exercises = [];
}
