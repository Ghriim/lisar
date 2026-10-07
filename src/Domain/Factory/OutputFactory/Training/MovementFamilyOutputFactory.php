<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\Output\Training\MovementFamilyDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class MovementFamilyOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<MovementFamilyDataModel> $movementFamilies
     *
     * @return list<MovementFamilyDataOutput>
     */
    public function buildMany(array $movementFamilies): array
    {
        $outputs = [];
        foreach ($movementFamilies as $movementFamily) {
            $outputs[] = $this->buildOne($movementFamily);
        }

        return $outputs;
    }

    public function buildOne(MovementFamilyDataModel $movementFamily): MovementFamilyDataOutput
    {
        return $this->mapper->map($movementFamily, MovementFamilyDataOutput::class);
    }
}
