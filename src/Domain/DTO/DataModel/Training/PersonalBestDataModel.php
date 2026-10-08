<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A personal best, the moment it was beaten. A row per time a record went up, so the rows of one
 * record — one owner, movement, kind and tier — are its progression, and the latest is the record
 * itself.
 *
 * Rows are never edited: they are rebuilt from the sets each time a write touches them, which is
 * what hands a record back to the previous holder when its set is unticked, corrected or removed.
 */
#[ORM\Table(name: 'personal_best')]
#[ORM\Index(name: 'personal_best_owner_movement', columns: ['owner_id', 'movement_id'])]
#[ORM\Entity]
class PersonalBestDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // Null for a record of a whole workout, across movements.
    #[ORM\ManyToOne(targetEntity: MovementDataModel::class)]
    #[ORM\JoinColumn(name: 'movement_id', nullable: true, onDelete: 'CASCADE')]
    public ?MovementDataModel $movement = null;

    // One of PersonalBestKindRegistry's codes.
    #[ORM\Column(length: 64)]
    public string $kind;

    // The tier within the kind — a number of reps, a distance in metres, a load — or null for a
    // kind without tiers.
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    public ?float $tier = null;

    // In the kind's unit: kilograms, reps, seconds, metres, seconds per kilometre.
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    public float $value;

    #[ORM\ManyToOne(targetEntity: WorkoutDataModel::class)]
    #[ORM\JoinColumn(name: 'workout_id', nullable: false, onDelete: 'CASCADE')]
    public WorkoutDataModel $workout;

    // The set that beat it, or null for a record a whole workout beat.
    #[ORM\ManyToOne(targetEntity: WorkoutSetDataModel::class, inversedBy: 'personalBests')]
    #[ORM\JoinColumn(name: 'workout_set_id', nullable: true, onDelete: 'CASCADE')]
    public ?WorkoutSetDataModel $set = null;

    // When the workout that beat it started: a set carries no moment of its own.
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $achievedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
