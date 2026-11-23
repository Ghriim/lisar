<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Training;

use App\Domain\DTO\DataModel\DataModelInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One set as a routine prescribes it: reps, and a share of the movement's estimated 1RM when the
 * movement tracks a load. Each target is a column of its own, empty when it does not apply: a
 * duration, a distance or a set type will come beside them the same way.
 */
#[ORM\Table(name: 'routine_set')]
#[ORM\Entity]
class RoutineSetDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RoutineExerciseDataModel::class, inversedBy: 'sets')]
    #[ORM\JoinColumn(name: 'routine_exercise_id', nullable: false, onDelete: 'CASCADE')]
    public RoutineExerciseDataModel $exercise;

    // Where it sits among its movement's sets. Gaps are harmless: only the order is read.
    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column(nullable: true)]
    public ?int $reps = null;

    // 1 to 100, by halves.
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    public ?float $percentageOfOneRepMax = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
