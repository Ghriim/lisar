<?php

declare(strict_types=1);

namespace App\Domain\DTO\DataModel;

use App\Domain\Registry\User\UserRoleRegistry;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user_account'), ORM\Entity]
class UserDataModel implements DataModelInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 32, unique: true)]
    public string $username;

    #[ORM\Column(length: 180, unique: true)]
    public string $email;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $avatar = null;

    #[ORM\Column(length: 32, options: ['default' => UserRoleRegistry::USER])]
    public string $role = UserRoleRegistry::USER;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $lastSignedInAt = null;

    /** @var Collection<int, UserIdentityDataModel> */
    #[ORM\OneToMany(targetEntity: UserIdentityDataModel::class, mappedBy: 'user', cascade: ['remove'])]
    public Collection $identities;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->identities = new ArrayCollection();
    }

    /**
     * A pure lookup over an association this data model already carries.
     */
    public function getIdentityForProvider(string $provider): ?UserIdentityDataModel
    {
        foreach ($this->identities as $identity) {
            if ($provider === $identity->provider) {
                return $identity;
            }
        }

        return null;
    }
}
