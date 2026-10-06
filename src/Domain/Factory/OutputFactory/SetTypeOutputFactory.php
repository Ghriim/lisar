<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\Output\Workout\SetTypeDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class SetTypeOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<SetTypeDataModel> $setTypes
     *
     * @return list<SetTypeDataOutput>
     */
    public function buildMany(array $setTypes): array
    {
        $outputs = [];
        foreach ($setTypes as $setType) {
            $outputs[] = $this->buildOne($setType);
        }

        return $outputs;
    }

    public function buildOne(SetTypeDataModel $setType): SetTypeDataOutput
    {
        return $this->mapper->map($setType, SetTypeDataOutput::class);
    }
}
