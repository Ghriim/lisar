<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Category;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Category\CategoryEditableConstraint;
use App\Domain\Validation\Constraint\Category\CategoryUnusedConstraint;
use App\Domain\Validation\Validator\Category\DeleteCategoryValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeleteCategoryValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeleteCategoryValidator())->validate($this->anyCurrentUser(), $this->category(true), 0);
    }

    public function testItRefusesACommonCategory(): void
    {
        $this->assertRefused(['id' => [CategoryEditableConstraint::CATEGORY_NOT_EDITABLE]], fn () => (new DeleteCategoryValidator())->validate($this->anyCurrentUser(), $this->category(false), 0));
    }

    public function testItRefusesACategoryTasksSitIn(): void
    {
        $this->assertRefused(['id' => [CategoryUnusedConstraint::CATEGORY_IN_USE]], fn () => (new DeleteCategoryValidator())->validate($this->anyCurrentUser(), $this->category(true), 3));
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $this->assertRefused(['id' => [CategoryEditableConstraint::CATEGORY_NOT_EDITABLE, CategoryUnusedConstraint::CATEGORY_IN_USE]], fn () => (new DeleteCategoryValidator())->validate($this->anyCurrentUser(), $this->category(false), 3));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function category(bool $isPersonal): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->owner = true === $isPersonal ? new UserDataModel() : null;

        return $category;
    }
}
