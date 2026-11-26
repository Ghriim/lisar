<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Todo;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A list of tasks, the account's own. Every account has one made with it — « A faire », its
 * default — which can be renamed but never archived nor deleted, and takes any task created
 * without a TODO named. Every task sits in exactly one.
 *
 * Titles are unique per account, case and surrounding spaces aside: the title is stored trimmed,
 * and the column's case-insensitive collation makes the index treat « Maison » and « maison » as
 * one.
 */
#[ORM\Table(name: 'todo')]
#[ORM\UniqueConstraint(name: 'todo_owner_title', columns: ['owner_id', 'title'])]
#[ORM\Entity]
class TodoDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    #[ORM\Column(length: 125)]
    public string $title;

    /** One of TodoStatusRegistry. */
    #[ORM\Column(length: 16)]
    public string $status;

    #[ORM\Column]
    public bool $isDefault = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
