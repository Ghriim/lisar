<?php

declare(strict_types=1);

namespace App\Domain\Registry\Training;

/**
 * The colours a set type can be shown in. A fixed palette the administrator picks from, so every
 * type sits well on both themes; each front end paints its own shade for a code. The API answers
 * the code, never a hex value.
 */
interface SetTypeColourRegistry
{
    public const string RED = 'red';
    public const string ORANGE = 'orange';
    public const string YELLOW = 'yellow';
    public const string GREEN = 'green';
    public const string TEAL = 'teal';
    public const string BLUE = 'blue';
    public const string PURPLE = 'purple';
    public const string PINK = 'pink';
    public const string GREY = 'grey';

    /** Every code, for the admin form's choices and the validator that guards them. */
    public const array ALL = [
        self::RED,
        self::ORANGE,
        self::YELLOW,
        self::GREEN,
        self::TEAL,
        self::BLUE,
        self::PURPLE,
        self::PINK,
        self::GREY,
    ];
}
