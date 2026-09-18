<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;

interface HydrationPresetPersisterGateway
{
    public function create(HydrationPresetDataModel $preset): HydrationPresetDataModel;

    public function update(HydrationPresetDataModel $preset): HydrationPresetDataModel;

    public function delete(HydrationPresetDataModel $preset): void;
}
