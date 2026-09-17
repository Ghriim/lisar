<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;

/**
 * A reference category is visible to everyone and editable by no one but the back-office. The
 * answer says exactly that, rather than pretending the category does not exist: the person can
 * see it, so denying its existence would only be confusing.
 */
final readonly class CategoryEditableConstraint
{
    public const string CATEGORY_NOT_EDITABLE = 'category_not_editable';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(CategoryDataModel $category, array $violations = []): array
    {
        if (false === $category->isPersonal()) {
            $violations['id'][] = self::CATEGORY_NOT_EDITABLE;
        }

        return $violations;
    }
}
