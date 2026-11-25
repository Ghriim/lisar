<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Gamification;

/** The Statut window: the global level, worked out on all the experience, then each stat by group. */
final class StatusDataOutput
{
    public ProgressDataOutput $progress;

    /**
     * Physical, then mental.
     *
     * @var list<StatGroupDataOutput>
     */
    public array $groups = [];
}
