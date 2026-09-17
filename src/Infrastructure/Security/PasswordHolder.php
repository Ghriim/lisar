<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * The bare minimum symfony/security's hasher needs: something that carries a hash.
 *
 * It exists so that no data model has to implement a framework interface — Domain stays free of
 * Security, and `password_hashers` keyed on PasswordAuthenticatedUserInterface still applies.
 */
final readonly class PasswordHolder implements PasswordAuthenticatedUserInterface
{
    public function __construct(private ?string $passwordHash = null)
    {
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }
}
