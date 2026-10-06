<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\WorkoutDataModel;

use function count;
use function sort;

/**
 * A new order names every block of the workout, each once, and nothing else: a block left out
 * would have no place, and a stranger's would land in the wrong workout.
 */
final readonly class WorkoutBlockOrderConstraint
{
    public const string MISMATCH = 'block_ids_mismatch';

    /**
     * @param list<int>                   $blockIds
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(array $blockIds, WorkoutDataModel $workout, array $violations = []): array
    {
        $expected = [];
        foreach ($workout->blocks as $block) {
            $expected[] = $block->id;
        }

        $given = $blockIds;
        sort($expected);
        sort($given);

        if (count($expected) !== count($given) || $expected !== $given) {
            $violations['blockIds'][] = self::MISMATCH;
        }

        return $violations;
    }
}
