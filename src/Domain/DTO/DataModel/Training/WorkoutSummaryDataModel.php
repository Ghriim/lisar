<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * What a workout adds up to, kept rather than worked out at each read: one per workout, created
 * with it, its totals moved each time a set is ticked, unticked, corrected or removed, its record
 * count each time the records are rebuilt. The history and the bilan read it as it stands.
 *
 * Only done sets count: a set still to do has added nothing yet. A total stays null while no done
 * set measures it — a workout of push-ups moved no load — rather than zero.
 */
#[ORM\Table(name: 'workout_summary')]
#[ORM\Entity]
class WorkoutSummaryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    // Not mapped back from the workout: an inverse one-to-one is fetched with every workout read.
    #[ORM\OneToOne(targetEntity: WorkoutDataModel::class)]
    #[ORM\JoinColumn(name: 'workout_id', nullable: false, unique: true, onDelete: 'CASCADE')]
    public WorkoutDataModel $workout;

    #[ORM\Column]
    public int $setCount = 0;

    // Reps times load; a unilateral set counts both sides.
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    public ?float $volumeInKilograms = null;

    #[ORM\Column(nullable: true)]
    public ?int $durationInSeconds = null;

    #[ORM\Column(nullable: true)]
    public ?int $distanceInMetres = null;

    // The personal bests it beat, by one of its sets or as a whole.
    #[ORM\Column]
    public int $personalBestCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
