<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Planning;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\Output\Planning\TaskPlanningDataOutput;
use App\Domain\DTO\Output\Planning\TaskPlanningEntryDataOutput;
use App\Domain\DTO\Output\Planning\TaskPlanningSlotDataOutput;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use DateTimeImmutable;

final readonly class TaskPlanningOutputFactory
{
    public function __construct(
        private PlanningScheduleOutputFactory $scheduleOutputFactory,
        private CategoryOutputFactory $categoryOutputFactory,
    ) {
    }

    /**
     * @param list<TaskPlanningEntryDataModel>                                                                                $entries
     * @param list<array{entry: TaskPlanningEntryDataModel, day: DateTimeImmutable, state: string, task: TaskDataModel|null}> $slots
     */
    public function buildMany(array $entries, array $slots): TaskPlanningDataOutput
    {
        $output = new TaskPlanningDataOutput();
        $output->entries = array_map($this->buildOne(...), $entries);

        foreach ($slots as $slot) {
            $one = new TaskPlanningSlotDataOutput();
            $one->entryId = (int) $slot['entry']->id;
            $one->taskId = $slot['task']?->id;
            $one->day = (string) DateDataTransformer::dateToDayString($slot['day']);
            $one->state = $slot['state'];
            $output->slots[] = $one;
        }

        return $output;
    }

    public function buildOne(TaskPlanningEntryDataModel $entry): TaskPlanningEntryDataOutput
    {
        $output = new TaskPlanningEntryDataOutput();
        $output->id = (int) $entry->id;
        $output->title = $entry->title;
        $output->description = $entry->description;
        $output->category = null === $entry->category ? null : $this->categoryOutputFactory->buildOne($entry->category);
        $output->todoId = (int) $entry->todo->id;
        $output->schedule = $this->scheduleOutputFactory->buildOne($entry);

        return $output;
    }
}
