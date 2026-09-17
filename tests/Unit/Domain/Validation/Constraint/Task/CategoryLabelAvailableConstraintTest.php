<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class CategoryLabelAvailableConstraintTest extends TestCase
{
    public function testItAcceptsAFreeLabel(): void
    {
        self::assertSame([], CategoryLabelAvailableConstraint::validate(null, null));
    }

    public function testItRejectsALabelTakenByAReferenceCategory(): void
    {
        self::assertSame(
            ['label' => [CategoryLabelAvailableConstraint::LABEL_ALREADY_USED]],
            CategoryLabelAvailableConstraint::validate($this->buildCategory(1), null),
        );
    }

    public function testItRejectsALabelTakenByTheAccountsOwnCategory(): void
    {
        self::assertSame(
            ['label' => [CategoryLabelAvailableConstraint::LABEL_ALREADY_USED]],
            CategoryLabelAvailableConstraint::validate(null, $this->buildCategory(1)),
        );
    }

    public function testItComplainsOnceEvenWhenBothCollide(): void
    {
        $violations = CategoryLabelAvailableConstraint::validate(
            $this->buildCategory(1),
            $this->buildCategory(2),
        );

        self::assertCount(1, $violations['label']);
    }

    /**
     * Renaming a category to the name it already has is not a collision with itself.
     */
    public function testACategoryMayKeepItsOwnLabel(): void
    {
        self::assertSame(
            [],
            CategoryLabelAvailableConstraint::validate(null, $this->buildCategory(7), 7),
        );
    }

    private function buildCategory(int $id): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->id = $id;
        $category->label = 'Home';

        return $category;
    }
}
