<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

interface TaskPlanningEntryProviderGateway
{
    /** An entry by its id alone, with its category: whose it is is the validator's to check. */
    public function findOneById(int $id): ?TaskPlanningEntryDataModel;

    /**
     * Every entry of the owner's, with its category, the earliest start time first — those within
     * the day before — then by id. Not bounded by dates: a person plans a handful.
     *
     * @return list<TaskPlanningEntryDataModel>
     */
    public function findAllForOwner(UserDataModel $owner): array;

    /**
     * Every recurring entry, whoever's, whose span holds that day — started on it or before, not
     * ended before it — with its owner and category: those the daily run reads the rule of.
     *
     * @return list<TaskPlanningEntryDataModel>
     */
    public function findRecurringSpanning(DateTimeImmutable $day): array;
}
