<?php

declare(strict_types=1);

namespace App\Domain\Registry\Category;

/**
 * The icons a common category can wear — and through it, everything filed in it: a task, a
 * catalogue habit has no icon of its own. A fixed vocabulary the administrator picks from; each
 * front end draws its own glyph and word for a code. The API answers the code, never a drawing.
 */
interface CategoryIconRegistry
{
    public const string HOME = 'home';
    public const string RUN = 'run';
    public const string BOOK = 'book';
    public const string DUMBBELL = 'dumbbell';
    public const string DROPLET = 'droplet';
    public const string LEAF = 'leaf';
    public const string MOON = 'moon';
    public const string HEART = 'heart';
    public const string TARGET = 'target';
    public const string GUITAR = 'guitar';
    public const string BRUSH = 'brush';
    public const string NOTEBOOK = 'notebook';
    public const string MONITOR = 'monitor';

    /** Every code, for the admin form's choices and the validator that guards them. */
    public const array ALL = [
        self::HOME,
        self::RUN,
        self::BOOK,
        self::DUMBBELL,
        self::DROPLET,
        self::LEAF,
        self::MOON,
        self::HEART,
        self::TARGET,
        self::GUITAR,
        self::BRUSH,
        self::NOTEBOOK,
        self::MONITOR,
    ];
}
