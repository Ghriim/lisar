<?php

declare(strict_types=1);

namespace App\Domain\Registry\Todo;

/**
 * How pressing a task is. A fixed list rather than rows: it does not move enough to be worth a
 * back-office. Display only — each front end words and colours its own code — and the order the
 * list sorts by inside a category.
 */
interface TaskPriorityRegistry
{
    public const string HIGH = 'high';
    public const string NORMAL = 'normal';
    public const string LOW = 'low';

    /** Every code, most pressing first: the order a category's tasks sort in. */
    public const array ALL = [
        self::HIGH,
        self::NORMAL,
        self::LOW,
    ];

    /** The one a task created without a priority takes. */
    public const string DEFAULT = self::NORMAL;
}
