<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Todo;

use App\Domain\DTO\DataModel\Todo\TagDataModel;

interface TagPersisterGateway
{
    public function create(TagDataModel $tag): TagDataModel;

    /** @param TagDataModel[] $tags */
    public function createMany(array $tags): void;
}
