<?php

declare(strict_types=1);

namespace App\Domain\Session;

/**
 * Mints refresh tokens and reduces them to what we are willing to store.
 *
 * Only the hash is persisted: a dump of the session table must not let anyone resume a session.
 */
final readonly class RefreshTokenGenerator
{
    private const int BYTES = 32;

    public function generate(): string
    {
        return bin2hex(random_bytes(self::BYTES));
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
