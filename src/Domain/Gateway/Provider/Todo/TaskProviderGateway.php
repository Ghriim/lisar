<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Todo;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

interface TaskProviderGateway
{
    /** Whoever it belongs to: whether the caller may touch it is the validator's to say. */
    public function findOneById(int $id): ?TaskDataModel;

    /**
     * The account's own list: root tasks only, subtasks nested underneath, sorted by category
     * then by priority, most pressing first.
     *
     * @return list<TaskDataModel>
     */
    public function findAllForOwnerList(UserDataModel $owner, bool $isDone): array;

    /** Whether a category may still be deleted. */
    public function countForCategory(CategoryDataModel $category): int;

    /**
     * The tasks the owner's planning entries made for dates from `$from` to `$to`, both
     * included, each with its entry.
     *
     * @return list<TaskDataModel>
     */
    public function findPlannedForOwnerBetween(UserDataModel $owner, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * The tasks an entry made that are not done, whatever their date.
     *
     * @return list<TaskDataModel>
     */
    public function findOpenForPlanningEntry(TaskPlanningEntryDataModel $entry): array;

    /**
     * Which of those dates the entry already made a task for, done or not.
     *
     * @param list<DateTimeImmutable> $days
     *
     * @return list<string> the days, "YYYY-MM-DD"
     */
    public function findPlannedDatesForPlanningEntry(TaskPlanningEntryDataModel $entry, array $days): array;

    /**
     * Which entries already made their task for that day, whoever's.
     *
     * @return list<int> the entries' ids
     */
    public function findPlanningEntryIdsPlannedOn(DateTimeImmutable $day): array;
}
