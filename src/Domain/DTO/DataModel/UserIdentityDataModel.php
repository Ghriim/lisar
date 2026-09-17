<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use App\Domain\Registry\User\IdentityProviderRegistry;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user_identity')]
#[ORM\UniqueConstraint(name: 'user_identity_user_provider', columns: ['user_account_id', 'provider'])]
#[ORM\UniqueConstraint(name: 'user_identity_provider_external_id', columns: ['provider', 'external_id'])]
#[ORM\Entity]
class UserIdentityDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserDataModel::class, inversedBy: 'identities')]
    #[ORM\JoinColumn(name: 'user_account_id', nullable: false, onDelete: 'CASCADE')]
    public UserDataModel $user;

    #[ORM\Column(length: 32)]
    public string $provider = IdentityProviderRegistry::PASSWORD;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $externalId = null;

    // Only ever set for the password provider, and only ever a hash.
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $passwordHash = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;
}
