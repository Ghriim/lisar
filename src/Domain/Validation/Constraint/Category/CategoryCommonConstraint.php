<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;

/**
 * What everyone shares — a catalogue habit — only goes in a common category. A personal one,
 * even the administrator's own, is answered like a category that does not exist: it is private,
 * and would show up nowhere but on that one account.
 */
final readonly class CategoryCommonConstraint
{
    public const string CATEGORY_NOT_FOUND = 'category_not_found';

    /**
     * @param int|null                    $requestedId the id the caller asked for, if any
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?int $requestedId, ?CategoryDataModel $category, array $violations = []): array
    {
        if (null === $requestedId) {
            return $violations;
        }

        if (null === $category || true === $category->isPersonal()) {
            $violations['categoryId'][] = self::CATEGORY_NOT_FOUND;
        }

        return $violations;
    }
}
