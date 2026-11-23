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
 * One step of a routine: a movement, or several done back to back — a superset. A block never
 * stays empty: removing its last movement removes it.
 */
#[ORM\Table(name: 'routine_block')]
#[ORM\Entity]
class RoutineBlockDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RoutineDataModel::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(name: 'routine_id', nullable: false, onDelete: 'CASCADE')]
    public RoutineDataModel $routine;

    // Where it sits in the routine. Gaps are harmless: only the order is read.
    #[ORM\Column]
    public int $position = 0;

    // Persisted with it: a routine is copied whole in one flush. Deleted with it by the database,
    // not by Doctrine (§6.1).
    /** @var Collection<int, RoutineExerciseDataModel> */
    #[ORM\OneToMany(targetEntity: RoutineExerciseDataModel::class, mappedBy: 'block', cascade: ['persist'])]
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
}
