<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\StepDayDataModel;
use App\Domain\DTO\Output\Step\StepDayDataOutput;
use DateTimeImmutable;

final readonly class StepDayOutputFactory
{
    public function buildOne(StepDayDataModel $stepDay): StepDayDataOutput
    {
        $output = new StepDayDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($stepDay->day);
        $output->goalInSteps = $stepDay->goalInSteps;
        $output->countInSteps = $stepDay->countInSteps;
        $output->source = $stepDay->source;

        return $output;
    }

    /**
     * A day nothing has been recorded on yet. Reading must not write, so no row exists: the goal
     * is the one that would apply were something logged now, and the count stays null.
     */
    public function buildEmpty(DateTimeImmutable $day, int $goalInSteps): StepDayDataOutput
    {
        $output = new StepDayDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($day);
        $output->goalInSteps = $goalInSteps;

        return $output;
    }
}
