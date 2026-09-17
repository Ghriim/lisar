<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Admin;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * A back-office note. Never served to the account it is about.
 */
final class UserCommentDataOutput
{
    public int $id;

    public string $body;

    #[Map(source: 'author.id')]
    public int $authorId;

    #[Map(source: 'author.username')]
    public string $authorUsername;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $createdAt = null;
}
