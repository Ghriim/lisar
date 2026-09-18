<?php

declare(strict_types=1);

namespace App\Infrastructure\Persister;

use App\Domain\DTO\DataModel\HydrationPresetDataModel;
use App\Domain\Gateway\Persister\HydrationPresetPersisterGateway;

/**
 * @extends AbstractBaseMysqlPersister<HydrationPresetDataModel>
 */
final class HydrationPresetPersister extends AbstractBaseMysqlPersister implements HydrationPresetPersisterGateway
{
    public function create(HydrationPresetDataModel $preset): HydrationPresetDataModel
    {
        return $this->persistAndStampCreate($preset);
    }

    public function update(HydrationPresetDataModel $preset): HydrationPresetDataModel
    {
        return $this->persistAndStampUpdate($preset);
    }

    public function delete(HydrationPresetDataModel $preset): void
    {
        $this->persistDelete($preset);
    }
}
