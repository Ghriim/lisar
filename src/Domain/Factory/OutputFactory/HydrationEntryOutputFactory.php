<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DataTransformer\DateDataTransformer;
use App\Domain\DTO\DataModel\HydrationEntryDataModel;
use App\Domain\DTO\Output\Hydration\HydrationEntryDataOutput;
use App\Domain\Tracking\DayClock;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class HydrationEntryOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private DayClock $clock,
    ) {
    }

    /**
     * @param HydrationEntryDataModel[] $entries
     *
     * @return list<HydrationEntryDataOutput>
     */
    public function buildMany(array $entries): array
    {
        $outputs = [];
        foreach ($entries as $entry) {
            $outputs[] = $this->buildOne($entry);
        }

        return $outputs;
    }

    public function buildOne(HydrationEntryDataModel $entry): HydrationEntryDataOutput
    {
        $output = $this->mapper->map($entry, HydrationEntryDataOutput::class);

        // Stored as the instant it was, shown on the clock the day is counted on: "21:06 +02:00"
        // rather than the same moment written as 19:06 UTC, which nobody drank at.
        $output->recordedAt = DateDataTransformer::dateToString($this->clock->inDisplayZone($entry->recordedAt));

        return $output;
    }
}
