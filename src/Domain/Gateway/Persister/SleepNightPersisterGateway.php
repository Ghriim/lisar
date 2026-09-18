<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\SleepNightDataModel;

/**
 * No `delete`: a night is corrected, never removed. An unslept night is said by noting nothing.
 */
interface SleepNightPersisterGateway
{
    public function create(SleepNightDataModel $night): SleepNightDataModel;

    public function update(SleepNightDataModel $night): SleepNightDataModel;
}
