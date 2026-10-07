<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A movement — "Bench press (barbell)", "Plank", "Running". It targets one primary muscle and any
 * number of secondary ones, is done with zero or more equipments (none: bodyweight), and says
 * what a set of it records through the four `tracks…` flags.
 *
 * Two kinds, the way categories have two:
 * - `owner` null — a common movement, managed in the back-office. The only kind today.
 * - `owner` set — a person's own movement. Nothing creates one yet; the column is there so that
 *   the day it does, the common ones need no migration.
 *
 * The name is unique among the common movements, ignoring case. That is checked by the use case
 * and not by an index: a person's own movements will be allowed a name a common one has, as with
 * categories.
 */
#[ORM\Table(name: 'movement')]
#[ORM\Entity]
class MovementDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 128)]
    public string $name;

    // How it is done, for whoever has not done it before.
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ORM\Column(length: 512, nullable: true)]
    public ?string $videoUrl = null;

    #[ORM\ManyToOne(targetEntity: MovementFamilyDataModel::class)]
    #[ORM\JoinColumn(name: 'movement_family_id', nullable: false, onDelete: 'RESTRICT')]
    public MovementFamilyDataModel $movementFamily;

    #[ORM\ManyToOne(targetEntity: MuscleDataModel::class)]
    #[ORM\JoinColumn(name: 'primary_muscle_id', nullable: false, onDelete: 'RESTRICT')]
    public MuscleDataModel $primaryMuscle;

    // Never the primary muscle: that one is not secondary to itself.
    /** @var Collection<int, MuscleDataModel> */
    #[ORM\ManyToMany(targetEntity: MuscleDataModel::class)]
    #[ORM\JoinTable(name: 'movement_secondary_muscle')]
    #[ORM\JoinColumn(name: 'movement_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'muscle_id', onDelete: 'RESTRICT')]
    public Collection $secondaryMuscles;

    /** @var Collection<int, EquipmentDataModel> */
    #[ORM\ManyToMany(targetEntity: EquipmentDataModel::class)]
    #[ORM\JoinTable(name: 'movement_equipment')]
    #[ORM\JoinColumn(name: 'movement_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'equipment_id', onDelete: 'RESTRICT')]
    public Collection $equipments;

    // What a set records. At least one of reps, duration and distance; the weight comes on top.
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksReps = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksWeight = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksDuration = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $tracksDistance = false;

    // One side at a time — a lunge, a one-arm row. A logged set still covers both sides, its reps
    // counted per side: the flag only changes how a set reads.
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isUnilateral = false;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: true, onDelete: 'CASCADE')]
    public ?UserDataModel $owner = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->secondaryMuscles = new ArrayCollection();
        $this->equipments = new ArrayCollection();
    }

    /** What a workout may take on now: active, in an active family. */
    public function isOffered(): bool
    {
        return true === $this->isActive && true === $this->movementFamily->isActive;
    }
}
