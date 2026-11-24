<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** What a run of the planned tasks' creation made. */
final class PlannedTasksCreationDataOutput
{
    /** A calendar day, "YYYY-MM-DD". */
    public string $day;

    public int $createdCount;
}
