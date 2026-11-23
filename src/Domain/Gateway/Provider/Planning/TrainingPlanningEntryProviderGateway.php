<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface TrainingPlanningEntryProviderGateway
{
    /** An entry by its id alone, with its routine: whose it is is the validator's to check. */
    public function findOneById(int $id): ?TrainingPlanningEntryDataModel;

    /**
     * Every entry of the owner's, with its routine, the earliest start time first — those within
     * the day before — then by id. Not bounded by dates: a person plans a handful.
     *
     * @return list<TrainingPlanningEntryDataModel>
     */
    public function findAllForOwner(UserDataModel $owner): array;
}
