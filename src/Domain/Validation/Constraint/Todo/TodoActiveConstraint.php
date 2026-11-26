<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\Registry\Todo\TodoStatusRegistry;

/**
 * An archived TODO is put away: it is not renamed, and nothing in it — a task, its tick, its
 * star — changes until it is made active again.
 */
final readonly class TodoActiveConstraint
{
    public const string TODO_ARCHIVED = 'todo_archived';

    /**
     * @param string                      $field      where the answer goes: `id` on the TODO itself, `todoId` on what sits in it
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(TodoDataModel $todo, string $field = 'id', array $violations = []): array
    {
        if (TodoStatusRegistry::ACTIVE !== $todo->status) {
            $violations[$field][] = self::TODO_ARCHIVED;
        }

        return $violations;
    }
}
