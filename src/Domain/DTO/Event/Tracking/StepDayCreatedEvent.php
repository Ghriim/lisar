<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Tracking;

use App\Domain\DTO\DataModel\Tracking\StepDayDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A day's step count was noted for the first time. */
final readonly class StepDayCreatedEvent implements EventInterface
{
    public function __construct(public StepDayDataModel $stepDay)
    {
    }
}
