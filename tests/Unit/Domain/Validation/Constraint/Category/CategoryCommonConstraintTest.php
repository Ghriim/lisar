<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Validation\Constraint\Category\CategoryCommonConstraint;
use PHPUnit\Framework\TestCase;

final class CategoryCommonConstraintTest extends TestCase
{
    public function testItAcceptsNoCategoryAtAll(): void
    {
        self::assertSame([], CategoryCommonConstraint::validate(null, null));
    }

    public function testItAcceptsACommonCategory(): void
    {
        self::assertSame([], CategoryCommonConstraint::validate(1, new CategoryDataModel()));
    }

    /** Even the administrator's own: a personal label would show on one account only. */
    public function testItRejectsAPersonalCategory(): void
    {
        $category = new CategoryDataModel();
        $category->owner = new UserDataModel();

        self::assertSame(
            ['categoryId' => [CategoryCommonConstraint::CATEGORY_NOT_FOUND]],
            CategoryCommonConstraint::validate(1, $category),
        );
    }

    public function testItRejectsACategoryThatWasNotFound(): void
    {
        self::assertSame(
            ['categoryId' => [CategoryCommonConstraint::CATEGORY_NOT_FOUND]],
            CategoryCommonConstraint::validate(404, null),
        );
    }
}
