<?php

declare(strict_types=1);

namespace App\Domain\Factory\OutputFactory;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Output\Task\CategoryDataOutput;
use LogicException;

final readonly class CategoryOutputFactory
{
    /**
     * @param CategoryDataModel[] $categories
     *
     * @return list<CategoryDataOutput>
     */
    public function buildMany(array $categories): array
    {
        $outputs = [];
        foreach ($categories as $category) {
            $outputs[] = $this->buildOne($category);
        }

        return $outputs;
    }

    public function buildOne(CategoryDataModel $category): CategoryDataOutput
    {
        // Assembled by hand rather than mapped: "personal" is something the row means, not a
        // column it carries.
        $output = new CategoryDataOutput();
        $output->id = $category->id ?? throw new LogicException('Cannot build an output from an unsaved category.');
        $output->label = $category->label;
        $output->isPersonal = $category->isPersonal();

        return $output;
    }
}
