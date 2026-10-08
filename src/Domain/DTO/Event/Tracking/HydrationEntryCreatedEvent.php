<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Tracking;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A drink was logged. Its day already counts it. */
final readonly class HydrationEntryCreatedEvent implements EventInterface
{
    public function __construct(public HydrationEntryDataModel $entry)
    {
    }
}
