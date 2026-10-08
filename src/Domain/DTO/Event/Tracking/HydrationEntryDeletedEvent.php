<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Tracking;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A drink was removed. The day it was on stays, and no longer counts it. */
final readonly class HydrationEntryDeletedEvent implements EventInterface
{
    public function __construct(public HydrationDayDataModel $day)
    {
    }
}
