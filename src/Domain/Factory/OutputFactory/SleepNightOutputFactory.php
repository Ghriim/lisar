<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\SleepNightDataModel;
use App\Domain\DTO\Output\Sleep\SleepNightDataOutput;
use App\Domain\Tracking\DayClock;
use App\Domain\Tracking\SleepWindow;
use DateTimeImmutable;

final readonly class SleepNightOutputFactory
{
    public function __construct(private DayClock $clock)
    {
    }

    public function buildOne(SleepNightDataModel $night): SleepNightDataOutput
    {
        $output = new SleepNightDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($night->day);
        // Stored as the instants they are, shown on the clock the day is counted on: "23:30"
        // rather than the same moment written as 21:30 UTC, which nobody went to bed at.
        $output->bedtimeAt = DateDataTransformer::dateToString($this->clock->inDisplayZone($night->bedtimeAt));
        $output->wakeUpAt = DateDataTransformer::dateToString($this->clock->inDisplayZone($night->wakeUpAt));
        $output->durationInMinutes = SleepWindow::durationInMinutes($night->bedtimeAt, $night->wakeUpAt);
        $output->moodRating = $night->moodRating;

        return $output;
    }

    /** A day whose night has not been noted. Reading must not write, so no row exists yet. */
    public function buildEmpty(DateTimeImmutable $day): SleepNightDataOutput
    {
        $output = new SleepNightDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($day);

        return $output;
    }
}
