<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Planning;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * A training entry plans one of the caller's own routines. Someone else's is answered exactly like
 * one that does not exist.
 */
final readonly class RoutineUsableConstraint
{
    public const string ROUTINE_NOT_FOUND = 'routine_not_found';

    /**
     * @param RoutineDataModel|null       $routine    the routine asked for, if it exists
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?RoutineDataModel $routine, UserDataModel $owner, array $violations = []): array
    {
        if (null === $routine || null === $routine->owner || $routine->owner->id !== $owner->id) {
            $violations['routineId'][] = self::ROUTINE_NOT_FOUND;
        }

        return $violations;
    }
}
