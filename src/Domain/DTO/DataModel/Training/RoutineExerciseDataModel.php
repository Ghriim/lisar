<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A movement as a routine prescribes it: its sets, a note, and the rest after each set — all three
 * handed to the workout it starts. The same movement may come twice in a routine, as two of these.
 */
#[ORM\Table(name: 'routine_exercise')]
#[ORM\Entity]
class RoutineExerciseDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RoutineBlockDataModel::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(name: 'routine_block_id', nullable: false, onDelete: 'CASCADE')]
    public RoutineBlockDataModel $block;

    // RESTRICT: a movement a routine prescribes cannot be deleted, only retired.
    #[ORM\ManyToOne(targetEntity: MovementDataModel::class)]
    #[ORM\JoinColumn(name: 'movement_id', nullable: false, onDelete: 'RESTRICT')]
    public MovementDataModel $movement;

    // Where it sits in its block. Gaps are harmless: only the order is read.
    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $note = null;

    // The rest planned after each of its sets. None: no timer.
    #[ORM\Column(nullable: true)]
    public ?int $restInSeconds = null;

    // Persisted with it: a routine is copied whole in one flush. Deleted with it by the database,
    // not by Doctrine (§6.1).
    /** @var Collection<int, RoutineSetDataModel> */
    #[ORM\OneToMany(targetEntity: RoutineSetDataModel::class, mappedBy: 'exercise', cascade: ['persist'])]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    public Collection $sets;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->sets = new ArrayCollection();
    }
}
