<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\Output\Habits\HabitAdminDataOutput;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class HabitAdminOutputFactory
{
    public function __construct(
        private ObjectMapperInterface $mapper,
        private CategoryOutputFactory $categoryOutputFactory,
    ) {
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
        $output = $this->mapper->map($habit, HabitAdminDataOutput::class);

        if (null !== $habit->category) {
            $output->category = $this->categoryOutputFactory->buildOne($habit->category);
        }

        return $output;
    }
}
