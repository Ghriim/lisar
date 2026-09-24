<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\Output\Habit\HabitAdminDataOutput;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class HabitAdminOutputFactory
{
    public function __construct(private ObjectMapperInterface $mapper)
    {
    }

    /**
     * @param list<HabitDataModel> $habits
     *
     * @return list<HabitAdminDataOutput>
     */
    public function buildMany(array $habits): array
    {
        $outputs = [];
        foreach ($habits as $habit) {
            $outputs[] = $this->buildOne($habit);
        }

        return $outputs;
    }

    public function buildOne(HabitDataModel $habit): HabitAdminDataOutput
    {
        return $this->mapper->map($habit, HabitAdminDataOutput::class);
    }
}
