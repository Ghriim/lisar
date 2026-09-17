<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A task category. Two origins, one single use for the person:
 *
 * - `owner` null — a reference category, managed in the back-office. Everyone sees it, nobody
 *   renames or hides it.
 * - `owner` set — a personal category, created by that account and visible to it only.
 *
 * The set is specific to the todo list: the other domains will have their own.
 */
#[ORM\Table(name: 'task_category')]
#[ORM\Entity]
class CategoryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 32)]
    public string $label;

    // Null marks a reference category. MySQL treats NULLs as distinct in a unique index, so
    // label uniqueness is enforced by the validators rather than by the schema.
    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: true, onDelete: 'CASCADE')]
    public ?UserDataModel $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function isPersonal(): bool
    {
        return null !== $this->owner;
    }

    /** Whether that account is allowed to put a task in this category. */
    public function isUsableBy(UserDataModel $user): bool
    {
        return null === $this->owner || $this->owner->id === $user->id;
    }
}
