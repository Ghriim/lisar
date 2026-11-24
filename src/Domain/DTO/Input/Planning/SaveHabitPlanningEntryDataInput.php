<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Planning;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A habit entry, whole: the habit whose days it says and when. Creating and changing one send the
 * same, and a change replaces it all. The habit must be one the caller keeps: that needs the
 * database, and is the validator's.
 */
final readonly class SaveHabitPlanningEntryDataInput implements DataInputInterface
{
    public function __construct(
        // The catalogue habit's id, as the habits panel has it.
        #[Assert\Positive(message: 'habit_invalid')]
        public int $habitId = 0,

        #[Assert\Valid]
        public PlanningScheduleDataInput $schedule = new PlanningScheduleDataInput(),
    ) {
    }
}
