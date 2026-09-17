<?php

declare(strict_types=1);

namespace App\Domain\Registry\User;

/**
 * How a user proves who they are. Only PASSWORD is shipped; the others are modelled from the
 * start so that adding them later does not migrate the schema of every account.
 */
interface IdentityProviderRegistry
{
    public const string PASSWORD = 'password';
    public const string GOOGLE = 'google';
    public const string APPLE = 'apple';
}
