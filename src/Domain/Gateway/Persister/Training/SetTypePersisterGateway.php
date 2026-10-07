<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;

interface SetTypePersisterGateway
{
    public function create(SetTypeDataModel $setType): SetTypeDataModel;

    public function update(SetTypeDataModel $setType): SetTypeDataModel;

    /** @param SetTypeDataModel[] $setTypes */
    public function updateMany(array $setTypes): void;

    public function delete(SetTypeDataModel $setType): void;
}
