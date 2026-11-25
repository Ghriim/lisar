<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Gamification;

/** A heading over three stats: it has no level of its own. */
final class StatGroupDataOutput
{
    /** physical or mental. */
    public string $group;

    /** @var list<StatDataOutput> */
    public array $stats = [];
}
