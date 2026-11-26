<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;

/**
 * « A faire » is where a task goes when no TODO is named: it is always there, so it is neither
 * archived nor deleted. Renaming it is fine.
 */
final readonly class TodoNotDefaultConstraint
{
    public const string TODO_IS_DEFAULT = 'todo_is_default';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(TodoDataModel $todo, array $violations = []): array
    {
        if (true === $todo->isDefault) {
            $violations['id'][] = self::TODO_IS_DEFAULT;
        }

        return $violations;
    }
}
