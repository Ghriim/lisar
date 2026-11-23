<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Planning;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A training entry, whole: the routine it plans and when. Creating and changing one send the same,
 * and a change replaces it all. The routine must be the caller's: that needs the database, and is
 * the validator's.
 */
final readonly class SaveTrainingPlanningEntryDataInput implements DataInputInterface
{
    public function __construct(
        #[Assert\Positive(message: 'routine_invalid')]
        public int $routineId = 0,

        #[Assert\Valid]
        public PlanningScheduleDataInput $schedule = new PlanningScheduleDataInput(),
    ) {
    }
}
