<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

/**
 * A superset chains different movements: the same one twice in a block would be one movement done
 * twice in a row, which two of its sets already say. Twice in a routine, as two blocks, is fine.
 */
final readonly class RoutineBlockMovementsDistinctConstraint
{
    public const string DUPLICATED = 'movement_ids_duplicated';

    /**
     * @param list<int>                   $movementIds in the order given
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(array $movementIds, array $violations = []): array
    {
        if (count($movementIds) !== count(array_unique($movementIds))) {
            $violations['exercises'][] = self::DUPLICATED;
        }

        return $violations;
    }
}
