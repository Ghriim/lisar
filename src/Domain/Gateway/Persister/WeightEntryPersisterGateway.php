<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\WeightEntryDataModel;

/**
 * No `delete`: a weight is corrected, never removed. A deleted weight would mean "I did not
 * weigh myself", and that is said by not recording one.
 */
interface WeightEntryPersisterGateway
{
    public function create(WeightEntryDataModel $entry): WeightEntryDataModel;

    public function update(WeightEntryDataModel $entry): WeightEntryDataModel;
}
