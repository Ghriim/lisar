<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\User;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * The account as its owner sees it. It never carries anything about identities: no hash, no
 * provider, nothing that would hint at how the account signs in.
 */
final class UserDataOutput
{
    public int $id;

    public string $username;

    public string $email;

    public ?string $avatar = null;

    public bool $isActive;

    /** One of UserRoleRegistry. Shown to the account itself, and to the back-office. */
    public string $role;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $lastSignedInAt = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $createdAt = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $updatedAt = null;
}
