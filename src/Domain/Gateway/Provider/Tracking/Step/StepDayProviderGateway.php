<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Tracking\Step;

use App\Domain\DTO\DataModel\Tracking\StepDayDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

interface StepDayProviderGateway
{
    /** That day's count, or null when nothing was recorded on it. */
    public function findOneForOwnerAndDay(UserDataModel $owner, DateTimeImmutable $day): ?StepDayDataModel;
}
