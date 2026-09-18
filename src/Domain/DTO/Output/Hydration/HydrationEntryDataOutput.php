<?php

declare(strict_types=1);

namespace App\Domain\DTO\Output\Hydration;

use App\Domain\DataTransformer\DateDataTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;

final class HydrationEntryDataOutput
{
    public int $id;

    public int $volumeInMillilitres;

    #[Map(transform: [DateDataTransformer::class, 'dateToString'])]
    public ?string $recordedAt = null;
}
