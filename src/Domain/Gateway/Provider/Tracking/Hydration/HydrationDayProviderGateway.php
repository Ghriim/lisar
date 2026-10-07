<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

interface HydrationDayProviderGateway
{
    /** The day itself with everything logged on it, or null when nothing was. */
    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?HydrationDayDataModel;
}
