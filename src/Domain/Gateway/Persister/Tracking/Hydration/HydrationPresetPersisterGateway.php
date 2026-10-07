<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationPresetDataModel;

interface HydrationPresetPersisterGateway
{
    public function create(HydrationPresetDataModel $preset): HydrationPresetDataModel;

    public function update(HydrationPresetDataModel $preset): HydrationPresetDataModel;

    public function delete(HydrationPresetDataModel $preset): void;
}
