<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A block added at the end of a workout: one movement, or several done back to back — a superset,
 * in the order given, each with its own rest. That each one is offered needs the database:
 * WorkoutMovementsOfferedConstraint.
 */
final readonly class AddWorkoutBlockDataInput implements DataInputInterface
{
    /** A sanity ceiling, not a training opinion: nobody chains more in one superset. */
    public const int MAX_MOVEMENTS = 6;

    /**
     * @param list<AddWorkoutBlockExerciseDataInput> $exercises
     */
    public function __construct(
        #[Assert\Count(min: 1, max: self::MAX_MOVEMENTS, minMessage: 'exercises_required', maxMessage: 'exercises_too_many')]
        #[Assert\Unique(message: 'movement_ids_duplicated', normalizer: [self::class, 'movementIdOf'])]
        #[Assert\Valid]
        public array $exercises = [],
    ) {
    }

    /** What two exercises of one block may not share. */
    public static function movementIdOf(AddWorkoutBlockExerciseDataInput $exercise): int
    {
        return $exercise->movementId;
    }

    /** @return list<int> in the order given */
    public function movementIds(): array
    {
        return array_map(static fn (AddWorkoutBlockExerciseDataInput $exercise): int => $exercise->movementId, $this->exercises);
    }
}
