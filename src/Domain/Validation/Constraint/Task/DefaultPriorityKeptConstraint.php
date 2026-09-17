<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\PriorityDataModel;

/**
 * There is always exactly one default priority, because a task created without one has to get
 * something. So the default is never *unset* — it is given to another priority, which takes it
 * away from the one that had it.
 */
final readonly class DefaultPriorityKeptConstraint
{
    public const string DEFAULT_PRIORITY_REQUIRED = 'default_priority_required';

    /**
     * @param bool                        $isBecomingDefault what the caller is asking for
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        PriorityDataModel $priority,
        bool $isBecomingDefault,
        array $violations = [],
    ): array {
        if (true === $priority->isDefault && false === $isBecomingDefault) {
            $violations['isDefault'][] = self::DEFAULT_PRIORITY_REQUIRED;
        }

        return $violations;
    }
}
