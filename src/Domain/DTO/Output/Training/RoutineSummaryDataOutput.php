<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use Symfony\Component\ObjectMapper\Attribute\Map;

/** A routine as its list shows it: its name, and how many movements it holds. */
final class RoutineSummaryDataOutput
{
    public int $id;

    public string $name;

    /** A movement that comes twice counts twice. */
    #[Map(if: false)]
    public int $movementCount = 0;
}
