<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Gamification;

use App\Domain\DTO\DataModel\Gamification\ExperienceGainDataModel;

interface ExperienceGainPersisterGateway
{
    public function create(ExperienceGainDataModel $gain): ExperienceGainDataModel;
}
