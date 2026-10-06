<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A movement as done in one workout: its sets, and a note about how it went. The same movement may
 * come twice in a workout, as two of these.
 */
#[ORM\Table(name: 'workout_exercise')]
#[ORM\Entity]
class WorkoutExerciseDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkoutBlockDataModel::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(name: 'workout_block_id', nullable: false, onDelete: 'CASCADE')]
    public WorkoutBlockDataModel $block;

    // RESTRICT: a movement a workout logged cannot be deleted, only retired.
    #[ORM\ManyToOne(targetEntity: MovementDataModel::class)]
    #[ORM\JoinColumn(name: 'movement_id', nullable: false, onDelete: 'RESTRICT')]
    public MovementDataModel $movement;

    // Where it sits in its block. Gaps are harmless: only the order is read.
    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $note = null;

    // Removed with it in memory too, not only by the database's cascade: a child left in the
    // identity map would be found again, orphaned, at the next flush.
    /** @var Collection<int, WorkoutSetDataModel> */
    #[ORM\OneToMany(targetEntity: WorkoutSetDataModel::class, mappedBy: 'exercise', cascade: ['remove'])]
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

    /** The position the next set takes: after every one already there. */
    public function nextSetPosition(): int
    {
        $position = 0;
        foreach ($this->sets as $set) {
            $position = max($position, $set->position + 1);
        }

        return $position;
    }

    /** @return list<WorkoutSetDataModel> in the order they were logged */
    public function orderedSets(): array
    {
        $sets = array_values($this->sets->toArray());
        usort($sets, static fn (WorkoutSetDataModel $a, WorkoutSetDataModel $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        return $sets;
    }
}
