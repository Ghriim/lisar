<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\Output\Habit\HabitCatalogItemDataOutput;

use function in_array;

final readonly class HabitCatalogItemOutputFactory
{
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
        $output->icon = $habit->icon;
        $output->sourceKind = $habit->sourceKind;
        $output->trackerKind = $habit->trackerKind;
        $output->trackerThreshold = $habit->trackerThreshold;
        $output->isSubscribed = $isSubscribed;

        return $output;
    }
}
