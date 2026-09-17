<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;

final readonly class PriorityLabelAvailableConstraint
{
    public const string LABEL_ALREADY_USED = 'priority_label_already_used';

    /**
     * @param PriorityDataModel|null      $existing   the priority already carrying that label, if any
     * @param int|null                    $exceptId   the priority being renamed, which may keep its own
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        ?PriorityDataModel $existing,
        ?int $exceptId = null,
        array $violations = [],
    ): array {
        if (null === $existing) {
            return $violations;
        }

        if (null !== $exceptId && $existing->id === $exceptId) {
            return $violations;
        }

        $violations['label'][] = self::LABEL_ALREADY_USED;

        return $violations;
    }
}
