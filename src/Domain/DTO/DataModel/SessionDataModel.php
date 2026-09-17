<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One live sign-in. The refresh token is what materialises it, and rotation replaces one row
 * with another rather than extending this one.
 */
#[ORM\Table(name: 'user_session')]
#[ORM\Entity]
class SessionDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class)]
    #[ORM\JoinColumn(name: 'user_account_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $user;

    // Only the SHA-256 of the refresh token is stored: a dump of this table must not let anyone
    // resume a session.
    #[ORM\Column(length: 64, unique: true)]
    public string $refreshTokenHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $expiresAt;

    // Set when the session is rotated, dropped by a sign-out, or killed by a token reuse.
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function isUsable(DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && $this->expiresAt > $now;
    }
}
