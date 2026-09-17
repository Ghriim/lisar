<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\DTO\Output\Session\SessionDataOutput;
use App\Domain\Session\AccessTokenIssuerInterface;

final readonly class SessionOutputFactory
{
    public function __construct(private AccessTokenIssuerInterface $accessTokenIssuer)
    {
    }

    /**
     * @param string $refreshToken the raw token, which only ever exists in memory and in the
     *                             cookie the controller sets
     */
    public function buildOne(SessionDataModel $session, string $accessToken, string $refreshToken): SessionDataOutput
    {
        $output = new SessionDataOutput();
        $output->accessToken = $accessToken;
        $output->expiresIn = $this->accessTokenIssuer->getTtlInSeconds();
        $output->refreshToken = $refreshToken;
        $output->refreshTokenExpiresAt = $session->expiresAt;

        return $output;
    }
}
