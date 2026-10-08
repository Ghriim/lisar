<?php

declare(strict_types=1);

namespace App\Domain\DTO\Event\Tracking;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\Event\EventInterface;

/** A drink's volume was corrected. */
final readonly class HydrationEntryUpdatedEvent implements EventInterface
{
    public function __construct(public HydrationEntryDataModel $entry)
    {
    }
}
