<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\WeightEntryDataModel;
use App\Domain\DTO\Output\Weight\WeightDataOutput;
use App\Domain\Tracking\DayClock;

final readonly class WeightOutputFactory
{
    public function __construct(private DayClock $clock)
    {
    }

    public function buildOne(WeightEntryDataModel $entry): WeightDataOutput
    {
        $output = new WeightDataOutput();
        $output->weightInKilograms = $entry->weightInKilograms;
        $output->day = DateDataTransformer::dateToDayString($entry->day);
        // Stored as the instant it was, shown on the clock the day is counted on.
        $output->recordedAt = DateDataTransformer::dateToString($this->clock->inDisplayZone($entry->recordedAt));
        $output->isFromToday = $this->clock->isCurrentDay($entry->day);

        return $output;
    }

    /** An account that has never recorded a weight. Reading must not write, so nothing exists. */
    public function buildEmpty(): WeightDataOutput
    {
        return new WeightDataOutput();
    }
}
