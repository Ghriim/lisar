<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\TagDataModel;

interface TagPersisterGateway
{
    public function create(TagDataModel $tag): TagDataModel;

    /** @param TagDataModel[] $tags */
    public function createMany(array $tags): void;
}
