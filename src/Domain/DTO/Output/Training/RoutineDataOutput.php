<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use Symfony\Component\ObjectMapper\Attribute\Map;

/** A routine, whole: its blocks in order, their movements, their prescribed sets. */
final class RoutineDataOutput
{
    public int $id;

    public string $name;

    /** @var list<RoutineBlockDataOutput> */
    #[Map(if: false)]
    public array $blocks = [];
}
