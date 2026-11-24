<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;

final readonly class TrainingPlanningEntryDataModelFactory
{
    public function __construct(
        private PlanningEntryDataModelFactory $planningEntryFactory,
    ) {
    }

    /** A new entry of `$owner`'s, planning `$routine` on `$schedule`. */
    public function build(UserDataModel $owner, RoutineDataModel $routine, PlanningScheduleDataInput $schedule): TrainingPlanningEntryDataModel
    {
        $entry = new TrainingPlanningEntryDataModel();
        $entry->owner = $owner;
        $this->apply($entry, $routine, $schedule);

        return $entry;
    }

    /** The entry replaced whole by what was sent. */
    public function apply(TrainingPlanningEntryDataModel $entry, RoutineDataModel $routine, PlanningScheduleDataInput $schedule): void
    {
        $entry->routine = $routine;
        $this->planningEntryFactory->applySchedule($entry, $schedule);
    }
}
