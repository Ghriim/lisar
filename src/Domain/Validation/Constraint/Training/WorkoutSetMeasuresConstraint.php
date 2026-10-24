<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;

/**
 * A set carries exactly what its movement tracks: every measure the movement tracks is required,
 * and one it does not track is refused — a weight on a push-up, a distance on a bench press would
 * be numbers nothing reads.
 *
 * The load alone may be missing: a set still to do may wait for it. Whether a done set has it is
 * WorkoutSetWeightPresentConstraint's to say.
 */
final readonly class WorkoutSetMeasuresConstraint
{
    public const string REPS_REQUIRED = 'reps_required';
    public const string REPS_NOT_TRACKED = 'reps_not_tracked';
    public const string WEIGHT_NOT_TRACKED = 'weight_not_tracked';
    public const string DURATION_REQUIRED = 'duration_required';
    public const string DURATION_NOT_TRACKED = 'duration_not_tracked';
    public const string DISTANCE_REQUIRED = 'distance_required';
    public const string DISTANCE_NOT_TRACKED = 'distance_not_tracked';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        MovementDataModel $movement,
        ?int $reps,
        ?float $weightInKilograms,
        ?int $durationInSeconds,
        ?int $distanceInMetres,
        array $violations = [],
    ): array {
        $violations = self::check($movement->tracksReps, null !== $reps, 'reps', self::REPS_REQUIRED, self::REPS_NOT_TRACKED, $violations);
        $violations = self::check($movement->tracksWeight, null !== $weightInKilograms, 'weightInKilograms', null, self::WEIGHT_NOT_TRACKED, $violations);
        $violations = self::check($movement->tracksDuration, null !== $durationInSeconds, 'durationInSeconds', self::DURATION_REQUIRED, self::DURATION_NOT_TRACKED, $violations);

        return self::check($movement->tracksDistance, null !== $distanceInMetres, 'distanceInMetres', self::DISTANCE_REQUIRED, self::DISTANCE_NOT_TRACKED, $violations);
    }

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    private static function check(bool $isTracked, bool $isGiven, string $field, ?string $required, string $notTracked, array $violations): array
    {
        if (null !== $required && true === $isTracked && false === $isGiven) {
            $violations[$field][] = $required;
        }
        if (false === $isTracked && true === $isGiven) {
            $violations[$field][] = $notTracked;
        }

        return $violations;
    }
}
