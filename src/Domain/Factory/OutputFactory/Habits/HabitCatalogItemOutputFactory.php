<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\Output\Habits\HabitCatalogItemDataOutput;
use App\Domain\Factory\OutputFactory\Category\CategoryOutputFactory;

use function in_array;

final readonly class HabitCatalogItemOutputFactory
{
    public function __construct(private CategoryOutputFactory $categoryOutputFactory)
    {
    }

    /**
     * @param list<HabitDataModel> $habits
     * @param list<int>            $subscribedHabitIds the ids the person actively keeps
     *
     * @return list<HabitCatalogItemDataOutput>
     */
    public function buildMany(array $habits, array $subscribedHabitIds): array
    {
        $outputs = [];
        foreach ($habits as $habit) {
            $outputs[] = $this->buildOne($habit, in_array($habit->id, $subscribedHabitIds, true));
        }

        return $outputs;
    }

    public function buildOne(HabitDataModel $habit, bool $isSubscribed): HabitCatalogItemDataOutput
    {
        $output = new HabitCatalogItemDataOutput();
        $output->habitId = $habit->id ?? 0;
        $output->name = $habit->name;
        $output->sourceKind = $habit->sourceKind;
        $output->trackerKind = $habit->trackerKind;
        $output->trackerThreshold = $habit->trackerThreshold;
        $output->isSubscribed = $isSubscribed;
        $output->category = null === $habit->category
            ? null
            : $this->categoryOutputFactory->buildOne($habit->category);

        return $output;
    }
}
