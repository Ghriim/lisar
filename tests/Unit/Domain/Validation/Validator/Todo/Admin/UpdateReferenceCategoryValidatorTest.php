<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\Input\Todo\Admin\UpdateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Todo\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\Todo\Admin\UpdateReferenceCategoryValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateReferenceCategoryValidatorTest extends TestCase
{
    private UpdateReferenceCategoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateReferenceCategoryValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsARename(): void
    {
        $this->validator->validate(
            new UpdateReferenceCategoryDataInput('Maison'),
            $this->buildCategory(1),
            null,
        );

        $this->expectNotToPerformAssertions();
    }

    public function testACategoryMayKeepItsOwnLabel(): void
    {
        $category = $this->buildCategory(7);

        $this->validator->validate(new UpdateReferenceCategoryDataInput('Home'), $category, $category);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsALabelAnotherCommonCategoryHas(): void
    {
        try {
            $this->validator->validate(
                new UpdateReferenceCategoryDataInput('Work'),
                $this->buildCategory(1),
                $this->buildCategory(2),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    private function buildCategory(int $id): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->id = $id;
        $category->label = 'Home';
        $category->owner = null;

        return $category;
    }
}
