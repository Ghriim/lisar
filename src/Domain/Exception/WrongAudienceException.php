<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

/**
 * The credentials were right, but the account belongs to the other front end: an administrator
 * signing in to the website, or a user to the back-office. Its own answer, because retrying will
 * not help — the person is at the wrong door.
 */
final class WrongAudienceException extends Exception
{
    public const string ERROR_CODE = 'wrong_audience';

    public function __construct()
    {
        parent::__construct(self::ERROR_CODE);
    }
}
