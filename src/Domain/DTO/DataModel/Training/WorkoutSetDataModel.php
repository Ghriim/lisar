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
 * One set of a movement. It carries exactly the measures its movement tracks — reps, a weight, a
 * duration, a distance — and none of the others. For a unilateral movement a set covers both
 * sides, and its reps are counted per side.
 *
 * A set always carries a type: the default one for an ordinary working set, another to mark a
 * warm-up, a dropset.
 *
 * A set is logged before it is done — its measures are what is about to be lifted — and ticked
 * once it is. A finished workout holds only ticked sets: one is not finished while a set is left.
 */
#[ORM\Table(name: 'workout_set')]
#[ORM\Entity]
class WorkoutSetDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkoutExerciseDataModel::class, inversedBy: 'sets')]
    #[ORM\JoinColumn(name: 'workout_exercise_id', nullable: false, onDelete: 'CASCADE')]
    public WorkoutExerciseDataModel $exercise;

    // Where it sits among its movement's sets. Gaps are harmless: only the order is read.
    #[ORM\Column]
    public int $position = 0;

    // RESTRICT: a set type a set carries cannot be deleted, only retired.
    #[ORM\ManyToOne(targetEntity: SetTypeDataModel::class)]
    #[ORM\JoinColumn(name: 'set_type_id', nullable: false, onDelete: 'RESTRICT')]
    public SetTypeDataModel $setType;

    #[ORM\Column(nullable: true)]
    public ?int $reps = null;

    // Read back as it was written, like the weight tracker's: rounded by the use case to
    // AddWorkoutSetDataInput::DECIMALS, not by the column.
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    public ?float $weightInKilograms = null;

    #[ORM\Column(nullable: true)]
    public ?int $durationInSeconds = null;

    #[ORM\Column(nullable: true)]
    public ?int $distanceInMetres = null;

    // Rate of perceived exertion, 1 to 10 by halves.
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    public ?float $rpe = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isComplete = false;

    // The personal bests this set established.
    /** @var Collection<int, PersonalBestDataModel> */
    #[ORM\OneToMany(targetEntity: PersonalBestDataModel::class, mappedBy: 'set')]
    public Collection $personalBests;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->personalBests = new ArrayCollection();
    }
}
