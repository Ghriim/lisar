<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;

/**
 * Two TODOs of one account by the same title would be indistinguishable in the list beside the
 * page — case and surrounding spaces aside.
 */
final readonly class TodoTitleAvailableConstraint
{
    public const string TITLE_ALREADY_USED = 'todo_title_already_used';

    /**
     * @param TodoDataModel|null          $sameTitle  the account's TODO already by that title, if any
     * @param int|null                    $exceptId   the TODO being renamed, which may keep its own title
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?TodoDataModel $sameTitle, ?int $exceptId = null, array $violations = []): array
    {
        if (null === $sameTitle || (null !== $exceptId && $sameTitle->id === $exceptId)) {
            return $violations;
        }

        $violations['title'][] = self::TITLE_ALREADY_USED;

        return $violations;
    }
}
