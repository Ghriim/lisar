<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\Output\Planning\PlannedTasksCreationDataOutput;
use DateTimeImmutable;

final readonly class PlannedTasksCreationOutputFactory
{
    public function buildOne(DateTimeImmutable $day, int $createdCount): PlannedTasksCreationDataOutput
    {
        $output = new PlannedTasksCreationDataOutput();
        $output->day = (string) DateDataTransformer::dateToDayString($day);
        $output->createdCount = $createdCount;

        return $output;
    }
}
