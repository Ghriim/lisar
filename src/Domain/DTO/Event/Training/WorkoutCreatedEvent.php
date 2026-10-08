<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A workout was started — empty, or laid out like a past one. */
final readonly class WorkoutCreatedEvent implements EventInterface
{
    public function __construct(public WorkoutDataModel $workout)
    {
    }
}
