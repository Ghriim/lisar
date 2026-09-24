<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Persister;

use App\Domain\DTO\DataModel\StepDayDataModel;

/**
 * No `delete`: a day's count is corrected, never removed. A deleted count would mean "I did not
 * walk", and that is said by a count of zero, not by an absent row.
 */
interface StepDayPersisterGateway
{
    public function create(StepDayDataModel $stepDay): StepDayDataModel;

    public function update(StepDayDataModel $stepDay): StepDayDataModel;
}
