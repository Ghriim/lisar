<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Gamification;

final class StatDataOutput
{
    /** One of strength, endurance, agility, intelligence, spirit, willpower. */
    public string $stat;

    public ProgressDataOutput $progress;
}
