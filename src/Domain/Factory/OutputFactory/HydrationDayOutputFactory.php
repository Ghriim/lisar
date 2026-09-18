<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\HydrationDayDataModel;
use App\Domain\DTO\Output\Hydration\HydrationDayDataOutput;
use DateTimeImmutable;

final readonly class HydrationDayOutputFactory
{
    public function __construct(private HydrationEntryOutputFactory $entryOutputFactory)
    {
    }

    public function buildOne(HydrationDayDataModel $day): HydrationDayDataOutput
    {
        $output = new HydrationDayDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($day->day);
        $output->goalInMillilitres = $day->goalInMillilitres;
        $output->totalInMillilitres = $day->getTotalInMillilitres();
        $output->entries = $this->entryOutputFactory->buildMany($day->entries->toArray());

        return $output;
    }

    /**
     * A day nothing was logged on. Reading must not write, so no row exists yet — but the widget
     * still has a goal to show and a progress of zero.
     */
    public function buildEmpty(DateTimeImmutable $day, int $goalInMillilitres): HydrationDayDataOutput
    {
        $output = new HydrationDayDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($day);
        $output->goalInMillilitres = $goalInMillilitres;
        $output->totalInMillilitres = 0;
        $output->entries = [];

        return $output;
    }
}
