<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Validation\Constraint\Todo\CategoryEditableConstraint;
use PHPUnit\Framework\TestCase;

final class CategoryEditableConstraintTest extends TestCase
{
    public function testAPersonalCategoryIsEditable(): void
    {
        $category = new CategoryDataModel();
        $category->owner = new UserDataModel();

        self::assertSame([], CategoryEditableConstraint::validate($category));
    }

    public function testAReferenceCategoryIsNot(): void
    {
        $category = new CategoryDataModel();
        $category->owner = null;

        self::assertSame(
            ['id' => [CategoryEditableConstraint::CATEGORY_NOT_EDITABLE]],
            CategoryEditableConstraint::validate($category),
        );
    }
}
