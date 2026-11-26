<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Planning;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;

final readonly class TaskPlanningEntryDataModelFactory
{
    public function __construct(
        private PlanningEntryDataModelFactory $planningEntryFactory,
    ) {
    }

    /** A new entry of `$owner`'s, making tasks from the model sent, on its schedule. */
    public function build(UserDataModel $owner, ?CategoryDataModel $category, SaveTaskPlanningEntryDataInput $input): TaskPlanningEntryDataModel
    {
        $entry = new TaskPlanningEntryDataModel();
        $entry->owner = $owner;
        $this->apply($entry, $category, $input);

        return $entry;
    }

    /** The entry replaced whole by what was sent. */
    public function apply(TaskPlanningEntryDataModel $entry, ?CategoryDataModel $category, SaveTaskPlanningEntryDataInput $input): void
    {
        $entry->title = $input->title;
        $entry->description = $input->description;
        $entry->category = $category;
        $this->planningEntryFactory->applySchedule($entry, $input->schedule);
    }
}
