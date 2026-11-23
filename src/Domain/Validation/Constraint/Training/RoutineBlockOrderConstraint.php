<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;

/**
 * A new order names every block of the routine, each once, and nothing else: a block left out
 * would have no place, and a stranger's would land in the wrong routine.
 */
final readonly class RoutineBlockOrderConstraint
{
    public const string MISMATCH = 'block_ids_mismatch';

    /**
     * @param list<int>                   $blockIds
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(array $blockIds, RoutineDataModel $routine, array $violations = []): array
    {
        $expected = [];
        foreach ($routine->blocks as $block) {
            $expected[] = $block->id;
        }

        $given = $blockIds;
        sort($expected);
        sort($given);

        if ($expected !== $given) {
            $violations['blockIds'][] = self::MISMATCH;
        }

        return $violations;
    }
}
