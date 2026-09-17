<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;

/**
 * Two categories with the same name in the same list would be indistinguishable, and the person
 * sees the reference ones and their own as one list.
 */
final readonly class CategoryLabelAvailableConstraint
{
    public const string LABEL_ALREADY_USED = 'category_label_already_used';

    /**
     * @param CategoryDataModel|null      $reference  the reference category with that label, if any
     * @param CategoryDataModel|null      $personal   the account's own category with that label, if any
     * @param int|null                    $exceptId   the category being renamed, which may keep its own label
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(
        ?CategoryDataModel $reference,
        ?CategoryDataModel $personal,
        ?int $exceptId = null,
        array $violations = [],
    ): array {
        foreach ([$reference, $personal] as $existing) {
            if (null === $existing) {
                continue;
            }

            // The category being renamed is allowed to keep the label it already has. Spelled
            // out rather than compared straight: an unsaved category has a null id, and so does
            // a creation, which would otherwise look like a match.
            if (null !== $exceptId && $existing->id === $exceptId) {
                continue;
            }

            $violations['label'][] = self::LABEL_ALREADY_USED;

            return $violations;
        }

        return $violations;
    }
}
