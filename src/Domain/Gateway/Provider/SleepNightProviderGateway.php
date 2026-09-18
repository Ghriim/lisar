<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\SleepNightDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use DateTimeImmutable;

/**
 * No "latest" finder, unlike weight: the widget shows the night of the day in progress or
 * nothing at all. Yesterday's seven hours say nothing about how someone is today.
 */
interface SleepNightProviderGateway
{
    /** The night one woke up from on that day, or null when none was noted. */
    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?SleepNightDataModel;
}
