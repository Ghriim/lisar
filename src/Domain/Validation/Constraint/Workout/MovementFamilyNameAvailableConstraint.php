<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;

/**
 * Two families under one name, ignoring case, would split the same variants in two.
 */
final readonly class MovementFamilyNameAvailableConstraint
{
    public const string NAME_ALREADY_USED = 'movement_family_name_already_used';

    /**
     * @param MovementFamilyDataModel|null $withSameName the row already carrying that name, ignoring case, if any
     * @param int|null                     $exceptId     the row being renamed, which may keep its own name
     * @param array<string, list<string>>  $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?MovementFamilyDataModel $withSameName, ?int $exceptId = null, array $violations = []): array
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
