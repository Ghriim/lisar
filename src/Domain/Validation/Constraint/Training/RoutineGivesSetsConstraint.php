<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

/**
 * A workout started from a routine has something to do: a routine without a set, or whose every
 * movement was retired since, starts nothing.
 */
final readonly class RoutineGivesSetsConstraint
{
    public const string EMPTY_ROUTINE = 'routine_empty';

    /**
     * @param int                         $setCount   how many sets the workout it starts would hold
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $setCount, array $violations = []): array
    {
        if (0 === $setCount) {
            $violations['routine'][] = self::EMPTY_ROUTINE;
        }

        return $violations;
    }
}
