<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\SetTypeDataModel;

/**
 * A set takes on only a set type that exists and is active. One a set already carries and that was
 * retired since may stay: staying is not taking on.
 */
final readonly class WorkoutSetTypeUsableConstraint
{
    public const string UNKNOWN = 'set_type_unknown';
    public const string INACTIVE = 'set_type_inactive';

    /**
     * @param int|null                    $requestedId the set type asked for, null for none
     * @param SetTypeDataModel|null       $requested   the row behind that id, if it exists
     * @param SetTypeDataModel|null       $current     the set type the set carries today, null on a new set
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?int $requestedId, ?SetTypeDataModel $requested, ?SetTypeDataModel $current, array $violations = []): array
    {
        if (null === $requestedId) {
            return $violations;
        }

        if (null === $requested) {
            $violations['setTypeId'][] = self::UNKNOWN;

            return $violations;
        }

        $isKept = null !== $current && $current->id === $requested->id;
        if (false === $requested->isActive && false === $isKept) {
            $violations['setTypeId'][] = self::INACTIVE;
        }

        return $violations;
    }
}
