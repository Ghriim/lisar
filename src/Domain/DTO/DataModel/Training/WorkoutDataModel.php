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
 * One workout a person did, or is doing. It is logged live: started, filled set by set, finished.
 * `finishedAt` null means it is in progress, and a person has at most one in progress at a time —
 * the use case that starts one guards that, not an index.
 *
 * A workout is an ordered list of blocks; a block holds one movement, or several done back to back
 * — a superset. Everything stays editable once it is finished, except the two moments, which say
 * when it happened and are never rewritten.
 */
#[ORM\Table(name: 'workout')]
#[ORM\Index(name: 'workout_owner_started', columns: ['owner_id', 'started_at'])]
#[ORM\Entity]
class WorkoutDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // Optional: each front end words a default for a workout that has none.
    #[ORM\Column(length: 128, nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $note = null;

    // How the person felt overall, 1 to 5.
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    public ?int $feeling = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $finishedAt = null;

    // Removed with it in memory too, not only by the database's cascade: a child left in the
    // identity map would be found again, orphaned, at the next flush.
    /** @var Collection<int, WorkoutBlockDataModel> */
    #[ORM\OneToMany(targetEntity: WorkoutBlockDataModel::class, mappedBy: 'workout', cascade: ['remove'])]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    public Collection $blocks;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }

    public function isInProgress(): bool
    {
        return null === $this->finishedAt;
    }

    public function findBlock(int $blockId): ?WorkoutBlockDataModel
    {
        foreach ($this->blocks as $block) {
            if ($blockId === $block->id) {
                return $block;
            }
        }

        return null;
    }

    public function findExercise(int $exerciseId): ?WorkoutExerciseDataModel
    {
        foreach ($this->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                if ($exerciseId === $exercise->id) {
                    return $exercise;
                }
            }
        }

        return null;
    }

    public function findSet(int $setId): ?WorkoutSetDataModel
    {
        foreach ($this->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                foreach ($exercise->sets as $set) {
                    if ($setId === $set->id) {
                        return $set;
                    }
                }
            }
        }

        return null;
    }

    public function countSets(): int
    {
        $count = 0;
        foreach ($this->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                $count += $exercise->sets->count();
            }
        }

        return $count;
    }

    /** The sets logged but not ticked as done yet. */
    public function countIncompleteSets(): int
    {
        $count = 0;
        foreach ($this->blocks as $block) {
            foreach ($block->exercises as $exercise) {
                foreach ($exercise->sets as $set) {
                    if (false === $set->isComplete) {
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }

    /** The position the next block takes: after every one already there. */
    public function nextBlockPosition(): int
    {
        $position = 0;
        foreach ($this->blocks as $block) {
            $position = max($position, $block->position + 1);
        }

        return $position;
    }

    /**
     * The blocks in workout order. Sorted here rather than trusted from the collection: one
     * reordered in this request is still held in the order it was loaded in.
     *
     * @return list<WorkoutBlockDataModel>
     */
    public function orderedBlocks(): array
    {
        $blocks = array_values($this->blocks->toArray());
        usort($blocks, static fn (WorkoutBlockDataModel $a, WorkoutBlockDataModel $b): int => [$a->position, $a->id] <=> [$b->position, $b->id]);

        return $blocks;
    }
}
