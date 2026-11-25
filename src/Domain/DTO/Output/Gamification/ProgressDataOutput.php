<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Gamification;

/** Where some experience stands on the level curve: a stat's, or the whole of it. */
final class ProgressDataOutput
{
    public int $level;

    /** All the experience gathered. */
    public int $experience;

    /** How much of it counts towards the next level. */
    public int $experienceIntoLevel;

    /** What going to the next level takes, in all. */
    public int $experienceForNextLevel;
}
