<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\DTO\Output\Hydration\HydrationPresetDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class HydrationPresetOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param HydrationPresetDataModel[] $presets
     *
     * @return list<HydrationPresetDataOutput>
     */
    public function buildMany(array $presets): array
    {
        $outputs = [];
        foreach ($presets as $preset) {
            $outputs[] = $this->buildOne($preset);
        }

        return $outputs;
    }

    public function buildOne(HydrationPresetDataModel $preset): HydrationPresetDataOutput
    {
        return $this->mapper->map($preset, HydrationPresetDataOutput::class);
    }
}
