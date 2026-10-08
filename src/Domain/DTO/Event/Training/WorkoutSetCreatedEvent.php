<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Training;

use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A set was logged. */
final readonly class WorkoutSetCreatedEvent implements EventInterface
{
    public function __construct(public WorkoutSetDataModel $set)
    {
    }
}
