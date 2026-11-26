<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel\Category;

use App\Domain\DTO\DataModel\DataModelInterface;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A category, shared by every domain that sorts things — tasks, catalogue habits. Two origins:
 *
 * - `owner` null — a reference category, managed in the back-office. Everyone sees it, nobody
 *   renames or hides it.
 * - `owner` set — a personal category, created by that account and visible to it only.
 *
 * What may go in a personal one is the owner's own things (their tasks); what everyone shares (the
 * habit catalogue) only takes a reference category.
 */
#[ORM\Table(name: 'category')]
#[ORM\Entity]
class CategoryDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 32)]
    public string $label;

    // One of CategoryIconRegistry's codes — the icon of everything filed here, tasks and habits
    // having none of their own. Set on every reference category; a personal one has none.
    #[ORM\Column(length: 32, nullable: true)]
    public ?string $icon = null;

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

    /** Whether that account is allowed to put one of its own things in this category. */
    public function isUsableBy(UserDataModel $user): bool
    {
        return null === $this->owner || $this->owner->id === $user->id;
    }
}
