<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

/**
 * The credentials — a password or a refresh token — do not open a session.
 *
 * Deliberately one exception for "unknown e-mail", "wrong password" and "unusable refresh
 * token": telling them apart would tell a stranger which accounts exist.
 */
final class InvalidCredentialsException extends Exception
{
    public const string ERROR_CODE = 'invalid_credentials';

    public function __construct()
    {
        parent::__construct(self::ERROR_CODE);
    }
}
