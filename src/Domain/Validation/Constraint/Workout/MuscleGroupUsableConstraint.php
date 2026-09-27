<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;

/**
 * A muscle goes in a group that exists and is active. An inactive group takes no new muscle —
 * neither created in it nor moved into it — but a muscle already sitting in one stays there when
 * it is renamed: staying is not moving.
 */
final readonly class MuscleGroupUsableConstraint
{
    public const string MUSCLE_GROUP_NOT_FOUND = 'muscle_group_not_found';
    public const string MUSCLE_GROUP_INACTIVE = 'muscle_group_inactive';

    /**
     * @param MuscleGroupDataModel|null   $muscleGroup    the group asked for, null when no group has that id
     * @param int|null                    $currentGroupId the group the muscle sits in today; null on a creation
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        ?MuscleGroupDataModel $muscleGroup,
        ?int $currentGroupId = null,
        array $violations = [],
    ): array {
        if (null === $muscleGroup) {
            $violations['muscleGroupId'][] = self::MUSCLE_GROUP_NOT_FOUND;

            return $violations;
        }

        if (false === $muscleGroup->isActive && (null === $currentGroupId || $muscleGroup->id !== $currentGroupId)) {
            $violations['muscleGroupId'][] = self::MUSCLE_GROUP_INACTIVE;
        }

        return $violations;
    }
}
