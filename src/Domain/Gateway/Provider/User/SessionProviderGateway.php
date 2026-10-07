<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\User;

use App\Domain\DTO\DataModel\User\SessionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

interface SessionProviderGateway
{
    public function findOneByRefreshTokenHash(string $refreshTokenHash): ?SessionDataModel;

    /** @return list<SessionDataModel> */
    public function findAllLiveForUser(UserDataModel $user): array;
}
