<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

/** A workout, whole: its blocks in order, their movements, their sets. */
final class WorkoutDataOutput
{
    public int $id;

    /** Null when the person gave none; each front end words its own default. */
    public ?string $name = null;

    public ?string $note = null;

    /** 1 to 5, or null. */
    public ?int $feeling = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public string $startedAt;

    /** Null while it is in progress. */
    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $finishedAt = null;

    #[Map(if: false)]
    public bool $isInProgress;

    /** @var list<WorkoutBlockDataOutput> */
    #[Map(if: false)]
    public array $blocks = [];
}
