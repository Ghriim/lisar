<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\SetTypeDataModel;

interface SetTypePersisterGateway
{
    public function create(SetTypeDataModel $setType): SetTypeDataModel;

    public function update(SetTypeDataModel $setType): SetTypeDataModel;

    public function delete(SetTypeDataModel $setType): void;
}
