<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Planning;

/** The training planning over some days: every entry of the person's, and the slots they give. */
final class TrainingPlanningDataOutput
{
    /** @var list<TrainingPlanningEntryDataOutput> */
    public array $entries = [];

    /**
     * By day, then by start time — those within the day first.
     *
     * @var list<TrainingPlanningSlotDataOutput>
     */
    public array $slots = [];
}
