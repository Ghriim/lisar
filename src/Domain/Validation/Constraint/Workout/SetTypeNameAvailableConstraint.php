<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\SetTypeDataModel;

/**
 * Two set types under one name — "Dropset" and "dropset" — could not be told apart on a set.
 */
final readonly class SetTypeNameAvailableConstraint
{
    public const string NAME_ALREADY_USED = 'set_type_name_already_used';

    /**
     * @param SetTypeDataModel|null       $withSameName the row already carrying that name, ignoring case, if any
     * @param int|null                    $exceptId     the row being renamed, which may keep its own name
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?SetTypeDataModel $withSameName, ?int $exceptId = null, array $violations = []): array
    {
        if (null === $withSameName) {
            return $violations;
        }

        // Spelled out rather than compared straight: a creation has a null id, and so does an
        // unsaved row, which would otherwise look like a match.
        if (null !== $exceptId && $withSameName->id === $exceptId) {
            return $violations;
        }

        $violations['name'][] = self::NAME_ALREADY_USED;

        return $violations;
    }
}
