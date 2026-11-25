<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use function in_array;

/**
 * The stats a movement feeds: its primary one never among its secondary ones. That each is a
 * physical stat, and that there are two secondaries at most, is the input's own shape.
 */
final readonly class MovementStatsConstraint
{
    public const string PRIMARY_STAT_ALSO_SECONDARY = 'primary_stat_also_secondary';

    /**
     * @param list<string>                $secondaryStats de-duplicated
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(string $primaryStat, array $secondaryStats, array $violations = []): array
    {
        if ('' !== $primaryStat && true === in_array($primaryStat, $secondaryStats, true)) {
            $violations['secondaryStats'][] = self::PRIMARY_STAT_ALSO_SECONDARY;
        }

        return $violations;
    }
}
