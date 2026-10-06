<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Workout;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A block added at the end of a workout: one movement, or several done back to back — a superset,
 * in the order given. That each one is offered needs the database: WorkoutMovementsOfferedConstraint.
 */
final readonly class AddWorkoutBlockDataInput implements DataInputInterface
{
    /** A sanity ceiling, not a training opinion: nobody chains more in one superset. */
    public const int MAX_MOVEMENTS = 6;

    /**
     * @param list<int> $movementIds
     */
    public function __construct(
        #[Assert\Count(min: 1, max: self::MAX_MOVEMENTS, minMessage: 'movement_ids_required', maxMessage: 'movement_ids_too_many')]
        #[Assert\All([new Assert\Type(type: 'int', message: 'movement_id_invalid')])]
        #[Assert\Unique(message: 'movement_ids_duplicated')]
        public array $movementIds = [],
    ) {
    }
}
