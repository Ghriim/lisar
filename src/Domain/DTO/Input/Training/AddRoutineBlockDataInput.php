<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A block added at the end of a routine: one movement, or several done back to back — a superset,
 * in the order given, each with its own rest. Each must be offered and fit a routine, and appear
 * once: those need the database or the whole list, and are the validator's.
 */
final readonly class AddRoutineBlockDataInput implements DataInputInterface
{
    /** A sanity ceiling, not a training opinion: nobody chains more in one superset. */
    public const int MAX_MOVEMENTS = AddWorkoutBlockDataInput::MAX_MOVEMENTS;

    /**
     * @param list<AddRoutineBlockExerciseDataInput> $exercises
     */
    public function __construct(
        #[Assert\Count(min: 1, max: self::MAX_MOVEMENTS, minMessage: 'exercises_required', maxMessage: 'exercises_too_many')]
        #[Assert\Valid]
        public array $exercises = [],
    ) {
    }
}
