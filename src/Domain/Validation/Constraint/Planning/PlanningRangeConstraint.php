<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Planning;

use App\Domain\DataTransformer\DateDataTransformer;

/**
 * A planning is read a week at a time, a month at most: a range that ends before it starts, or
 * runs past six weeks, is refused rather than walked day by day.
 */
final readonly class PlanningRangeConstraint
{
    public const string RANGE_INVALID = 'range_invalid';
    public const int MAX_DAYS = 42;

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(string $from, string $to, array $violations = []): array
    {
        $first = DateDataTransformer::dayStringToDate($from);
        $last = DateDataTransformer::dayStringToDate($to);
        if (null === $first || null === $last) {
            // Malformed: said by the input's own shape already.
            return $violations;
        }

        $days = (int) $first->diff($last)->format('%r%a');
        if ($days < 0 || $days >= self::MAX_DAYS) {
            $violations['to'][] = self::RANGE_INVALID;
        }

        return $violations;
    }
}
