<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\Output\Task\PriorityDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class PriorityOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param PriorityDataModel[] $priorities
     *
     * @return list<PriorityDataOutput>
     */
    public function buildMany(array $priorities): array
    {
        $outputs = [];
        foreach ($priorities as $priority) {
            $outputs[] = $this->buildOne($priority);
        }

        return $outputs;
    }

    public function buildOne(PriorityDataModel $priority): PriorityDataOutput
    {
        return $this->mapper->map($priority, PriorityDataOutput::class);
    }
}
