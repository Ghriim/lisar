<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An administrator's note on an account, for internal follow-up: support, moderation.
 *
 * It is never exposed to the account it is about — only the back-office reads it.
 */
#[ORM\Table(name: 'user_comment')]
#[ORM\Entity]
class UserCommentDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    /** The account the note is about. */
    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'user_account_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $user;

    // The administrator who wrote it. Deliberately RESTRICT: a note must never end up
    // unattributed, and accounts are not deleted anyway.
    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'author_id', nullable: false, onDelete: 'RESTRICT')]
    public UserDataModel $author;

    #[ORM\Column(type: Types::TEXT)]
    public string $body;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
