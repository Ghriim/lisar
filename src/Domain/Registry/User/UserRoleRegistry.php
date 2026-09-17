<?php

declare(strict_types=1);

namespace App\Domain\Registry\User;

/**
 * The two roles the application knows. There is no way to sign up as an administrator: the first
 * one is created by a console command.
 */
interface UserRoleRegistry
{
    public const string USER = 'ROLE_USER';
    public const string ADMIN = 'ROLE_ADMIN';
}
