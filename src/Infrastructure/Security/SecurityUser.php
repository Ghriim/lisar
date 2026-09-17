<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Registry\User\IdentityProviderRegistry;
use LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * What symfony/security and lexik need a "user" to look like, built from a data model.
 *
 * The adapter exists so that UserDataModel never implements a framework interface: Domain stays
 * free of Security, and everything the firewall needs is assembled here instead.
 */
final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param non-empty-string $email the identifier the firewall works with
     */
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public bool $isActive,
        private ?string $passwordHash = null,
    ) {
    }

    public static function fromDataModel(UserDataModel $user): self
    {
        $email = $user->email;
        if ('' === $email) {
            throw new LogicException('Cannot build a security user from an account without an e-mail.');
        }

        return new self(
            id: $user->id ?? throw new LogicException('Cannot build a security user from an unsaved account.'),
            email: $email,
            role: $user->role,
            isActive: $user->isActive,
            passwordHash: $user->getIdentityForProvider(IdentityProviderRegistry::PASSWORD)?->passwordHash,
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }
}
