<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One step of a workout: a movement, or several done back to back — a superset. A block never
 * stays empty: removing its last movement removes it.
 */
#[ORM\Table(name: 'workout_block')]
#[ORM\Entity]
class WorkoutBlockDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkoutDataModel::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(name: 'workout_id', nullable: false, onDelete: 'CASCADE')]
    public WorkoutDataModel $workout;

    // Where it sits in the workout. Gaps are harmless: only the order is read.
    #[ORM\Column]
    public int $position = 0;

    // Removed with it in memory too, not only by the database's cascade: a child left in the
    // identity map would be found again, orphaned, at the next flush.
    /** @var Collection<int, WorkoutExerciseDataModel> */
    #[ORM\OneToMany(targetEntity: WorkoutExerciseDataModel::class, mappedBy: 'block', cascade: ['remove'])]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    public Collection $exercises;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
    }

    /** The position the next movement takes: after every one already there. */
    public function nextExercisePosition(): int
    {
        $position = 0;
        foreach ($this->exercises as $exercise) {
            $position = max($position, $exercise->position + 1);
        }

        return $position;
    }

    /** @return list<WorkoutExerciseDataModel> in block order */
    public function orderedExercises(): array
    {
        $exercises = array_values($this->exercises->toArray());
        usort($exercises, static fn (WorkoutExerciseDataModel $a, WorkoutExerciseDataModel $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        return $exercises;
    }
}
