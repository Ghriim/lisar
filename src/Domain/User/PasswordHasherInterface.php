<?php

declare(strict_types=1);

namespace App\Domain\User;

/**
 * The contract the Domain needs to turn a plain password into something storable. The algorithm
 * is an infrastructure concern and lives there; this is the seam that keeps it out of the use
 * cases.
 */
interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(string $hash, string $plainPassword): bool;
}
