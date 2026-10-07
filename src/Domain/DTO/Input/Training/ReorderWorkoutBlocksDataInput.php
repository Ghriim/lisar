<?php

declare(strict_types=1);

namespace App\Domain\DTO\Input\Training;

use App\Domain\DTO\Input\DataInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Every block of a workout, in its new order. All of them, each once: WorkoutBlockOrderConstraint
 * holds the list against the workout.
 */
final readonly class ReorderWorkoutBlocksDataInput implements DataInputInterface
{
    /**
     * @param list<int> $blockIds
     */
    public function __construct(
        #[Assert\All([new Assert\Type(type: 'int', message: 'block_id_invalid')])]
        public array $blockIds = [],
    ) {
    }
}
