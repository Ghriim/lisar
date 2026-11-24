<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory\Todo;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use DateTimeImmutable;

final readonly class TaskDataModelFactory
{
    /**
     * The task a planning entry makes for one of its dates: its model's title, description and
     * category, that date as its due date, the default priority. From then on it is an ordinary
     * task — only its link to the entry and its date say where it came from.
     */
    public function buildPlanned(TaskPlanningEntryDataModel $entry, DateTimeImmutable $day): TaskDataModel
    {
        // The calendar day alone, as a DATE column holds it, whatever zone `$day` came in.
        $date = DateDataTransformer::dayStringToDate($day->format('Y-m-d')) ?? $day;

        $task = new TaskDataModel();
        $task->owner = $entry->owner;
        $task->title = $entry->title;
        $task->description = $entry->description;
        $task->category = $entry->category;
        $task->priority = TaskPriorityRegistry::DEFAULT;
        $task->dueDate = $date;
        $task->planningEntry = $entry;
        $task->plannedDate = $date;

        return $task;
    }
}
