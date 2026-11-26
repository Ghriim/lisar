<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * What is put in a TODO — a task, a planning entry, a list asked for — goes in one of the
 * account's own, and an active one. Someone else's is answered exactly like one that does not
 * exist.
 */
final readonly class TodoUsableConstraint
{
    public const string TODO_NOT_FOUND = 'todo_not_found';

    /**
     * @param TodoDataModel|null          $todo       the TODO asked for, or the one it falls back on, already loaded
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?TodoDataModel $todo, UserDataModel $owner, array $violations = []): array
    {
        if (null === $todo || $todo->owner->id !== $owner->id) {
            $violations['todoId'][] = self::TODO_NOT_FOUND;

            return $violations;
        }

        return TodoActiveConstraint::validate($todo, 'todoId', $violations);
    }
}
