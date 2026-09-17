<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

/**
 * The credentials were right, but the account is deactivated. A dedicated answer, because the
 * person needs to know that an administrator has to undo it — retrying will not help.
 */
final class AccountDeactivatedException extends Exception
{
    public const string ERROR_CODE = 'account_deactivated';

    public function __construct()
    {
        parent::__construct(self::ERROR_CODE);
    }
}
