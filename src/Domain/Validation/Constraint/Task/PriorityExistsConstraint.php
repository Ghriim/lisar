<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;

final readonly class PriorityExistsConstraint
{
    public const string PRIORITY_NOT_FOUND = 'priority_not_found';

    /**
     * @param int|null                    $requestedId the id the caller asked for, if any
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?int $requestedId, ?PriorityDataModel $priority, array $violations = []): array
    {
        if (null !== $requestedId && null === $priority) {
            $violations['priorityId'][] = self::PRIORITY_NOT_FOUND;
        }

        return $violations;
    }
}
