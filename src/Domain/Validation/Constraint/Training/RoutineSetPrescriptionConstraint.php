<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

/**
 * A prescribed set carries what its movement calls for: reps, always — a routine holds only
 * movements that track them — and a share of the estimated 1RM exactly when the movement tracks a
 * load. A share on a push-up would be a number nothing reads.
 */
final readonly class RoutineSetPrescriptionConstraint
{
    public const string REPS_REQUIRED = 'reps_required';
    public const string PERCENTAGE_REQUIRED = 'percentage_required';
    public const string PERCENTAGE_NOT_TRACKED = 'percentage_not_tracked';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(MovementDataModel $movement, ?int $reps, ?float $percentageOfOneRepMax, array $violations = []): array
    {
        if (null === $reps) {
            $violations['reps'][] = self::REPS_REQUIRED;
        }
        if (true === $movement->tracksWeight && null === $percentageOfOneRepMax) {
            $violations['percentageOfOneRepMax'][] = self::PERCENTAGE_REQUIRED;
        }
        if (false === $movement->tracksWeight && null !== $percentageOfOneRepMax) {
            $violations['percentageOfOneRepMax'][] = self::PERCENTAGE_NOT_TRACKED;
        }

        return $violations;
    }
}
