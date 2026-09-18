<?php

declare(strict_types=1);

namespace App\Domain\Registry\Hydration;

/**
 * What a shortcut is drawn as. A code, not an image: the back-office ships no assets, and each
 * front end draws it with its own icon set — and words it in its own language.
 */
interface HydrationIconRegistry
{
    public const string GLASS = 'glass';
    public const string BOTTLE = 'bottle';
    public const string MUG = 'mug';
    public const string CAN = 'can';
    public const string CARAFE = 'carafe';

    /** @var list<string> */
    public const array ALL = [self::GLASS, self::BOTTLE, self::MUG, self::CAN, self::CARAFE];
}
