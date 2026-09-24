<?php

declare(strict_types=1);

namespace App\Domain\Registry\Habit;

/**
 * The icons a catalogue habit can wear. A fixed vocabulary the administrator picks from; each
 * front end draws its own glyph and word for a code, exactly as it does for the hydration
 * shortcuts. The API answers the code, never a drawing.
 */
interface HabitIconRegistry
{
    public const string RUN = 'run';
    public const string BOOK = 'book';
    public const string DUMBBELL = 'dumbbell';
    public const string DROPLET = 'droplet';
    public const string LEAF = 'leaf';
    public const string MOON = 'moon';
    public const string HEART = 'heart';
    public const string TARGET = 'target';

    /** Every code, for the admin form's choices and the validator that guards them. */
    public const array ALL = [
        self::RUN,
        self::BOOK,
        self::DUMBBELL,
        self::DROPLET,
        self::LEAF,
        self::MOON,
        self::HEART,
        self::TARGET,
    ];
}
