<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\DataModel\WeightEntryDataModel;
use DateTimeImmutable;

interface WeightEntryProviderGateway
{
    /**
     * The most recent measurement, whatever day it falls on, or null for an account that has
     * never recorded one. This is what the widget shows: someone who skipped three days still
     * sees where they stand.
     */
    public function findLatestForOwner(UserDataModel $owner): ?WeightEntryDataModel;

    /** That day's measurement, or null when none was recorded on it. */
    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?WeightEntryDataModel;
}
