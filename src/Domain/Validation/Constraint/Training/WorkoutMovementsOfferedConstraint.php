<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

use function in_array;

/**
 * A workout takes on only what is offered now: a movement that exists, is active and sits in an
 * active family. A movement already in a workout and retired since stays there — staying is not
 * taking on.
 */
final readonly class WorkoutMovementsOfferedConstraint
{
    public const string UNAVAILABLE = 'movement_unavailable';

    /**
     * @param list<int>                   $requestedIds
     * @param list<MovementDataModel>     $offered      the offered movements among those ids
     * @param string                      $field        the input field the ids came in
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(array $requestedIds, array $offered, string $field, array $violations = []): array
    {
        $offeredIds = [];
        foreach ($offered as $movement) {
            $offeredIds[] = $movement->id;
        }

        foreach ($requestedIds as $id) {
            if (false === in_array($id, $offeredIds, true)) {
                $violations[$field][] = self::UNAVAILABLE;

                return $violations;
            }
        }

        return $violations;
    }
}
