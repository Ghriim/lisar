<?php

declare(strict_types=1);

namespace App\Domain\Factory\DataModelFactory;

use App\Domain\DTO\DataModel\TagDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

use function in_array;

final readonly class TagDataModelFactory
{
    public function buildOne(string $label, UserDataModel $owner): TagDataModel
    {
        $tag = new TagDataModel();
        $tag->label = $label;
        $tag->owner = $owner;

        return $tag;
    }

    /**
     * Builds the tags of that list the account does not have yet, and nothing else: a tag is
     * created once and reused by every task that names it.
     *
     * @param list<string>   $labels
     * @param TagDataModel[] $existingTags
     *
     * @return list<TagDataModel>
     */
    public function buildMany(array $labels, UserDataModel $owner, array $existingTags): array
    {
        $existingLabels = [];
        foreach ($existingTags as $existingTag) {
            $existingLabels[] = $existingTag->label;
        }

        $tags = [];
        foreach ($labels as $label) {
            if (false === in_array($label, $existingLabels, true)) {
                $tags[] = $this->buildOne($label, $owner);
            }
        }

        return $tags;
    }
}
