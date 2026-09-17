<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Hashing as configured in config/packages/security.yaml, behind the Domain's own contract.
 */
final readonly class PasswordHasher implements PasswordHasherInterface
{
    public function __construct(private UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function hash(string $plainPassword): string
    {
        return $this->userPasswordHasher->hashPassword(new PasswordHolder(), $plainPassword);
    }

    public function verify(string $hash, string $plainPassword): bool
    {
        return $this->userPasswordHasher->isPasswordValid(new PasswordHolder($hash), $plainPassword);
    }
}
