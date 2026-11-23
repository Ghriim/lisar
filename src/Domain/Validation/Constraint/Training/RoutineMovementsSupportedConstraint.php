<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

/**
 * A routine prescribes reps, with or without a share of the 1RM: only a movement that tracks reps,
 * and neither a duration nor a distance, fits one for now. A plank, a run, a farmer walk wait for
 * the targets that would prescribe them.
 */
final readonly class RoutineMovementsSupportedConstraint
{
    public const string NOT_SUPPORTED = 'movement_not_supported_in_routine';

    /**
     * @param list<MovementDataModel>     $movements  the movements asked for, those that exist
     * @param string                      $field      the input field they came in
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(array $movements, string $field, array $violations = []): array
    {
        foreach ($movements as $movement) {
            if (false === $movement->tracksReps || true === $movement->tracksDuration || true === $movement->tracksDistance) {
                $violations[$field][] = self::NOT_SUPPORTED;

                return $violations;
            }
        }

        return $violations;
    }
}
