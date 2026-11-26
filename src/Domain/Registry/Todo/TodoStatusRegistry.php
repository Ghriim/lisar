<?php

declare(strict_types=1);

namespace App\Domain\Registry\Todo;

/**
 * Where a TODO stands. An archived one is put away: its tasks show nowhere, nothing in it may be
 * changed, and the one thing it still allows is to be made active again.
 */
interface TodoStatusRegistry
{
    public const string ACTIVE = 'active';
    public const string ARCHIVED = 'archived';

    public const array ALL = [
        self::ACTIVE,
        self::ARCHIVED,
    ];
}
