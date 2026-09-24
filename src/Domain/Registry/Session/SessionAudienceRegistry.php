<?php

declare(strict_types=1);

namespace App\Domain\Registry\Session;

use App\Domain\Registry\User\UserRoleRegistry;

/**
 * Which front end a session is opened for. The website and the back-office never share one: an
 * account belongs to exactly one of them, decided by its role, and signing in or out of one leaves
 * the other alone.
 */
interface SessionAudienceRegistry
{
    public const string WEBSITE = 'website';
    public const string ADMIN = 'admin';

    /** The one role an account must hold to open a session for each audience. */
    public const array REQUIRED_ROLE = [
        self::WEBSITE => UserRoleRegistry::USER,
        self::ADMIN => UserRoleRegistry::ADMIN,
    ];
}
