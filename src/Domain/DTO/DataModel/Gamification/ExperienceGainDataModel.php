<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Gamification;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Some experience earned in one stat. A stat's experience is the sum of its gains, and the global
 * level reads the sum of them all. A gain follows what earned it: an action taken back deletes
 * its gain, and nothing else ever takes experience away.
 *
 * What earns experience is not wired yet: the gains will say where each came from when it is.
 */
#[ORM\Table(name: 'experience_gain')]
#[ORM\Index(name: 'experience_gain_owner_stat', columns: ['owner_id', 'stat'])]
#[ORM\Entity]
class ExperienceGainDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    /** One of StatRegistry::ALL. */
    #[ORM\Column(length: 16)]
    public string $stat;

    // A whole number, above zero.
    #[ORM\Column]
    public int $amount;

    // The instant it was earned, in UTC.
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $earnedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
