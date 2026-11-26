<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Todo;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class TodoDataOutput
{
    public int $id;

    public string $title;

    /** One of TodoStatusRegistry. */
    public string $status;

    /** The account's « A faire »: never archived nor deleted, listed first. */
    public bool $isDefault;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $createdAt = null;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $updatedAt = null;
}
