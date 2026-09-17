<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A free-form tag, private to one account: there is no shared vocabulary.
 *
 * Tags are rows rather than a string column on the task so that an account can be offered the
 * tags it already uses, and so that filtering on one is a join instead of a LIKE.
 */
#[ORM\Table(name: 'task_tag')]
#[ORM\UniqueConstraint(name: 'task_tag_owner_label', columns: ['owner_id', 'label'])]
#[ORM\Entity]
class TagDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'owner_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $owner;

    #[ORM\Column(length: 32)]
    public string $label;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
