<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Session;

use DateTimeImmutable;
use JsonSerializable;

/**
 * What a sign-in answers with.
 *
 * The refresh token is carried here for the controller to put in an httpOnly cookie, and is
 * deliberately kept out of the JSON body: JsonSerializable is what declares that contract, so a
 * new field cannot leak into the payload by accident.
 */
final class SessionDataOutput implements JsonSerializable
{
    public string $accessToken;

    /** Seconds the access token stays valid. */
    public int $expiresIn;

    public string $tokenType = 'Bearer';

    public string $refreshToken;

    public DateTimeImmutable $refreshTokenExpiresAt;

    /**
     * @return array{accessToken: string, expiresIn: int, tokenType: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'expiresIn' => $this->expiresIn,
            'tokenType' => $this->tokenType,
        ];
    }
}
