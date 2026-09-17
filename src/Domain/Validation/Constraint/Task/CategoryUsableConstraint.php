<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;

/**
 * A task goes either in a reference category or in one of the account's own. Someone else's
 * personal category is answered exactly like a category that does not exist: its label is none
 * of this account's business.
 */
final readonly class CategoryUsableConstraint
{
    public const string CATEGORY_NOT_FOUND = 'category_not_found';

    /**
     * @param int|null                    $requestedId the id the caller asked for, if any
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        ?int $requestedId,
        ?CategoryDataModel $category,
        UserDataModel $owner,
        array $violations = [],
    ): array {
        if (null === $requestedId) {
            return $violations;
        }

        if (null === $category || false === $category->isUsableBy($owner)) {
            $violations['categoryId'][] = self::CATEGORY_NOT_FOUND;
        }

        return $violations;
    }
}
