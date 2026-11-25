<?php

declare(strict_types=1);

namespace App\Domain\Registry\Gamification;

/**
 * The six stats, in two groups. A group is a heading only: it has no level of its own. The order
 * of each list is the order the Statut window shows them in.
 */
interface StatRegistry
{
    public const string STRENGTH = 'strength';
    public const string ENDURANCE = 'endurance';
    public const string AGILITY = 'agility';

    public const string INTELLIGENCE = 'intelligence';
    public const string SPIRIT = 'spirit';
    public const string WILLPOWER = 'willpower';

    /** Fed by the movements of a workout: each names its own. */
    public const array PHYSICAL = [self::STRENGTH, self::ENDURANCE, self::AGILITY];

    /** Fed by the tasks and the habits. */
    public const array MENTAL = [self::INTELLIGENCE, self::SPIRIT, self::WILLPOWER];

    public const array ALL = [...self::PHYSICAL, ...self::MENTAL];

    public const string GROUP_PHYSICAL = 'physical';
    public const string GROUP_MENTAL = 'mental';

    /** Each group and its stats, in the order they are shown. */
    public const array GROUPS = [
        self::GROUP_PHYSICAL => self::PHYSICAL,
        self::GROUP_MENTAL => self::MENTAL,
    ];

    /** A movement names one primary stat and at most this many secondary ones. */
    public const int MAX_SECONDARY_STATS = 2;
}
