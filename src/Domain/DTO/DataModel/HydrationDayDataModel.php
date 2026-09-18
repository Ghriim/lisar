<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One person's day of drinking. It exists from the moment something is logged, and it carries
 * the goal that applied **that day**.
 *
 * Freezing the goal here is the whole point of this row: raising the goal from 1500 to 2000 must
 * not retroactively turn a day that was reached into a day that was missed. Nothing reads it yet;
 * the statistics that will are worth not having to reconstruct.
 */
#[ORM\Table(name: 'hydration_day')]
#[ORM\UniqueConstraint(name: 'hydration_day_owner_day', columns: ['owner_id', 'day'])]
#[ORM\Entity]
class HydrationDayDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    // A calendar day in the application's timezone, with no time of day.
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public DateTimeImmutable $day;

    #[ORM\Column]
    public int $goalInMillilitres;

    /** @var Collection<int, HydrationEntryDataModel> */
    #[ORM\OneToMany(targetEntity: HydrationEntryDataModel::class, mappedBy: 'hydrationDay', cascade: ['remove'])]
    public Collection $entries;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    /** What has been drunk, in millilitres. */
    public function getTotalInMillilitres(): int
    {
        $total = 0;
        foreach ($this->entries as $entry) {
            $total += $entry->volumeInMillilitres;
        }

        return $total;
    }
}
