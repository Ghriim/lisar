<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Training;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

/** A personal best the moment it was beaten: what, by how much, where. */
final class PersonalBestDataOutput
{
    public int $id;

    /** One of PersonalBestKindRegistry's codes; each front end words it. */
    public string $kind;

    /** A number of reps, a distance in metres or a load — or null for a kind without tiers. */
    public ?float $tier = null;

    /** In the kind's unit: kilograms, reps, seconds, metres, seconds per kilometre. */
    public float $value;

    /** Null for a record of a whole workout. */
    #[Map(if: false)]
    public ?int $movementId = null;

    #[Map(if: false)]
    public ?string $movementName = null;

    #[Map(if: false)]
    public int $workoutId;

    /** The set that beat it, or null for a record a whole workout beat. */
    #[Map(if: false)]
    public ?int $setId = null;

    /** When the workout that beat it started. */
    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public string $achievedAt;
}
