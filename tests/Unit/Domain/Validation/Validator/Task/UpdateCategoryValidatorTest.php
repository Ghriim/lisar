<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\UpdateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryEditableConstraint;
use App\Domain\Validation\Validator\Task\UpdateCategoryValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateCategoryValidatorTest extends TestCase
{
    private UpdateCategoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateCategoryValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsRenamingAPersonalCategory(): void
    {
        $this->validator->validate(new UpdateCategoryDataInput('Sport'), $this->buildPersonal(1), null, null);

        $this->expectNotToPerformAssertions();
    }

    public function testACategoryMayKeepItsOwnLabel(): void
    {
        $category = $this->buildPersonal(7);

        $this->validator->validate(new UpdateCategoryDataInput('Sport'), $category, null, $category);

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesToRenameAReferenceCategory(): void
    {
        $category = new CategoryDataModel();
        $category->id = 1;
        $category->owner = null;

        try {
            $this->validator->validate(new UpdateCategoryDataInput('Maison'), $category, null, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(CategoryEditableConstraint::CATEGORY_NOT_EDITABLE, $exception->violations['id']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $reference = new CategoryDataModel();
        $reference->id = 1;
        $reference->owner = null;

        try {
            $this->validator->validate(new UpdateCategoryDataInput(''), $reference, $reference, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('label', $exception->violations);
            self::assertArrayHasKey('id', $exception->violations);
        }
    }

    private function buildPersonal(int $id): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->id = $id;
        $category->label = 'Sport';
        $category->owner = new UserDataModel();

        return $category;
    }
}
