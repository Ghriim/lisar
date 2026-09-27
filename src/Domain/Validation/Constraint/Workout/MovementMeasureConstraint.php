<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

/**
 * A set of a movement has to record something: repetitions, a duration or a distance. The weight
 * only ever comes on top of one of them — a load alone says nothing about what was done with it.
 */
final readonly class MovementMeasureConstraint
{
    public const string MEASURE_REQUIRED = 'measure_required';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(bool $tracksReps, bool $tracksDuration, bool $tracksDistance, array $violations = []): array
    {
        if (false === $tracksReps && false === $tracksDuration && false === $tracksDistance) {
            $violations['measure'][] = self::MEASURE_REQUIRED;
        }

        return $violations;
    }
}
