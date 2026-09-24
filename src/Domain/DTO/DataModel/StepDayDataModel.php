<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One person's day of walking. At most one per day, by constraint: a step count is a running
 * total, not a log of increments, so a day holds a single number that later saves overwrite.
 *
 * The count is cumulative on purpose. A manual save types the total read off a watch; the mobile
 * sync that will come sends the day's total too. Summing either would double-count, so the row is
 * written idempotently — see UseCase\Step\SaveStepDayUseCase.
 *
 * The goal is frozen into the row the first time the day is written, exactly as the hydration day
 * freezes its own: raising the goal from 10000 to 12000 must not retroactively turn a day that was
 * reached into a day that was missed.
 */
#[ORM\Table(name: 'step_day')]
#[ORM\UniqueConstraint(name: 'step_day_owner_day', columns: ['owner_id', 'day'])]
#[ORM\Entity]
class StepDayDataModel implements DataModelInterface
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
    public int $goalInSteps;

    #[ORM\Column]
    public int $countInSteps;

    // Which writer last set the count — see Domain\Registry\Step\StepSourceRegistry. Kept so the
    // mobile sync to come can be told apart from a manual entry without a schema change.
    #[ORM\Column(length: 32)]
    public string $source;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
