<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

/** A finished workout as the history lists it: when, what, how much. */
final class WorkoutSummaryDataOutput
{
    public int $id;

    public ?string $name = null;

    public ?int $feeling = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public string $startedAt;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $finishedAt = null;

    /**
     * The movements done, in workout order, each named once.
     *
     * @var list<string>
     */
    #[Map(if: false)]
    public array $movementNames = [];

    #[Map(if: false)]
    public int $setCount = 0;
}
