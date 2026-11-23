<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;

/**
 * The set named in the route is in the routine named with it. One of another routine — someone
 * else's, or another of the caller's — is answered like one that does not exist: its id says
 * nothing.
 */
final readonly class RoutineSetInRoutineConstraint
{
    public const string NOT_FOUND = 'routine_set_not_found';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(RoutineSetDataModel $set, RoutineDataModel $routine, array $violations = []): array
    {
        // The same row is the same instance within a request: the identity map sees to it.
        if ($routine !== $set->exercise->block->routine) {
            $violations['setId'][] = self::NOT_FOUND;
        }

        return $violations;
    }
}
