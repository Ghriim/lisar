<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

interface SessionProviderGateway
{
    public function findOneByRefreshTokenHash(string $refreshTokenHash): ?SessionDataModel;

    /** @return list<SessionDataModel> */
    public function findAllLiveForUser(UserDataModel $user): array;
}
