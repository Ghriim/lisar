<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Gamification;

use App\Domain\DTO\DataModel\User\UserDataModel;

interface ExperienceGainProviderGateway
{
    /**
     * The owner's experience in each stat they earned some in, added up in one query. A stat
     * absent from the answer has none.
     *
     * @return array<string, int> stat => experience
     */
    public function sumByStatForOwner(UserDataModel $owner): array;
}
