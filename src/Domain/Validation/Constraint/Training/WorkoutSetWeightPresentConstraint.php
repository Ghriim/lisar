<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

/**
 * A set may wait for its load while it is still to do — one started from a routine before its
 * movement had an estimated 1RM — but a done set carries the load its movement tracks: a set is
 * ticked only once it has one.
 */
final readonly class WorkoutSetWeightPresentConstraint
{
    public const string WEIGHT_REQUIRED = 'weight_required';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(MovementDataModel $movement, ?float $weightInKilograms, bool $isDone, array $violations = []): array
    {
        if (true === $isDone && true === $movement->tracksWeight && null === $weightInKilograms) {
            $violations['weightInKilograms'][] = self::WEIGHT_REQUIRED;
        }

        return $violations;
    }
}
