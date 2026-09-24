<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\StepDayDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use DateTimeImmutable;

interface StepDayProviderGateway
{
    /** That day's count, or null when nothing was recorded on it. */
    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?StepDayDataModel;
}
