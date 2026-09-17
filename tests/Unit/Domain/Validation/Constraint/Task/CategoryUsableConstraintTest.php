<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Validation\Constraint\Task\CategoryUsableConstraint;
use PHPUnit\Framework\TestCase;

final class CategoryUsableConstraintTest extends TestCase
{
    public function testItAcceptsNoCategoryAtAll(): void
    {
        self::assertSame([], CategoryUsableConstraint::validate(null, null, $this->buildUser(1)));
    }

    public function testItAcceptsAReferenceCategory(): void
    {
        $category = new CategoryDataModel();
        $category->owner = null;

        self::assertSame([], CategoryUsableConstraint::validate(1, $category, $this->buildUser(1)));
    }

    public function testItAcceptsTheAccountsOwnCategory(): void
    {
        $owner = $this->buildUser(1);

        $category = new CategoryDataModel();
        $category->owner = $owner;

        self::assertSame([], CategoryUsableConstraint::validate(1, $category, $owner));
    }

    /**
     * Someone else's personal category is answered exactly like one that does not exist.
     */
    public function testItRejectsSomeoneElsesPersonalCategory(): void
    {
        $category = new CategoryDataModel();
        $category->owner = $this->buildUser(2);

        self::assertSame(
            ['categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND]],
            CategoryUsableConstraint::validate(1, $category, $this->buildUser(1)),
        );
    }

    public function testItRejectsACategoryThatWasNotFound(): void
    {
        self::assertSame(
            ['categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND]],
            CategoryUsableConstraint::validate(404, null, $this->buildUser(1)),
        );
    }

    private function buildUser(int $id): UserDataModel
    {
        $user = new UserDataModel();
        $user->id = $id;

        return $user;
    }
}
