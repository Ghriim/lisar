<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\MuscleDataModel;

use function count;
use function in_array;

/**
 * What a movement targets: a primary muscle that exists, secondary ones that all exist, the
 * primary never among the secondary ones — and every one of them available, active in an active
 * group.
 *
 * Available is asked of what the movement takes on, not of what it already has: a muscle retired
 * since it was put on the movement stays there when the movement is changed otherwise.
 */
final readonly class MovementMusclesConstraint
{
    public const string PRIMARY_MUSCLE_NOT_FOUND = 'primary_muscle_not_found';
    public const string PRIMARY_MUSCLE_UNAVAILABLE = 'primary_muscle_unavailable';
    public const string SECONDARY_MUSCLE_NOT_FOUND = 'secondary_muscle_not_found';
    public const string SECONDARY_MUSCLE_UNAVAILABLE = 'secondary_muscle_unavailable';
    public const string PRIMARY_MUSCLE_ALSO_SECONDARY = 'primary_muscle_also_secondary';

    /**
     * @param MuscleDataModel|null        $primaryMuscle      the primary muscle asked for, null when none has that id
     * @param list<int>                   $secondaryMuscleIds the secondary ids asked for, de-duplicated
     * @param list<MuscleDataModel>       $secondaryMuscles   the muscles found among them
     * @param MovementDataModel|null      $current            the movement being changed; null on a creation
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        int $primaryMuscleId,
        ?MuscleDataModel $primaryMuscle,
        array $secondaryMuscleIds,
        array $secondaryMuscles,
        ?MovementDataModel $current = null,
        array $violations = [],
    ): array {
        $held = self::heldIds($current);

        if (null === $primaryMuscle) {
            $violations['primaryMuscleId'][] = self::PRIMARY_MUSCLE_NOT_FOUND;
        } elseif (false === self::isAvailable($primaryMuscle) && false === in_array($primaryMuscle->id, $held, true)) {
            $violations['primaryMuscleId'][] = self::PRIMARY_MUSCLE_UNAVAILABLE;
        }

        if (count($secondaryMuscles) < count($secondaryMuscleIds)) {
            $violations['secondaryMuscleIds'][] = self::SECONDARY_MUSCLE_NOT_FOUND;
        }

        foreach ($secondaryMuscles as $secondaryMuscle) {
            if (false === self::isAvailable($secondaryMuscle) && false === in_array($secondaryMuscle->id, $held, true)) {
                $violations['secondaryMuscleIds'][] = self::SECONDARY_MUSCLE_UNAVAILABLE;
                break;
            }
        }

        if (true === in_array($primaryMuscleId, $secondaryMuscleIds, true)) {
            $violations['secondaryMuscleIds'][] = self::PRIMARY_MUSCLE_ALSO_SECONDARY;
        }

        return $violations;
    }

    private static function isAvailable(MuscleDataModel $muscle): bool
    {
        return true === $muscle->isActive && true === $muscle->muscleGroup->isActive;
    }

    /**
     * Every muscle the movement already targets, in either role.
     *
     * @return list<int|null>
     */
    private static function heldIds(?MovementDataModel $current): array
    {
        if (null === $current) {
            return [];
        }

        $ids = [$current->primaryMuscle->id];
        foreach ($current->secondaryMuscles as $secondaryMuscle) {
            $ids[] = $secondaryMuscle->id;
        }

        return $ids;
    }
}
