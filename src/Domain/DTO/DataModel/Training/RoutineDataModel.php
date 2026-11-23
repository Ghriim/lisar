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
 * The blueprint of a workout: blocks, movements and sets, each set prescribed as reps and a share
 * of the movement's estimated 1RM rather than as a load. A workout is started from it, its loads
 * worked out from the records of the day.
 *
 * Its owner may be empty: a routine shared with everyone, made from the back-office.
 */
#[ORM\Table(name: 'routine')]
#[ORM\Index(name: 'routine_owner_name', columns: ['owner_id', 'name'])]
#[ORM\Entity]
class RoutineDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: true, onDelete: 'CASCADE')]
    public ?UserDataModel $owner = null;

    // Shorter than a workout's: the workout it starts adds the day to it.
    #[ORM\Column(length: 100)]
    public string $name;

    // Persisted with it: a routine is copied whole, with its blocks, movements and sets, in one
    // flush. Deleted with it by the database, not by Doctrine (§6.1).
    /** @var Collection<int, RoutineBlockDataModel> */
    #[ORM\OneToMany(targetEntity: RoutineBlockDataModel::class, mappedBy: 'routine', cascade: ['persist'])]
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
}
