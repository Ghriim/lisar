<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;

/**
 * A muscle name is unique across every group, ignoring case: a movement lists its muscles by name.
 */
final readonly class MuscleNameAvailableConstraint
{
    public const string NAME_ALREADY_USED = 'muscle_name_already_used';

    /**
     * @param MuscleDataModel|null        $withSameName the row already carrying that name, ignoring case, if any
     * @param int|null                    $exceptId     the row being renamed, which may keep its own name
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?MuscleDataModel $withSameName, ?int $exceptId = null, array $violations = []): array
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
