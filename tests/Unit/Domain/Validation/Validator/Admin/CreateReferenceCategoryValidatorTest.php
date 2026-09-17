<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Admin;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Admin\CreateReferenceCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\Admin\CreateReferenceCategoryValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateReferenceCategoryValidatorTest extends TestCase
{
    private CreateReferenceCategoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateReferenceCategoryValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAFreeLabel(): void
    {
        $this->validator->validate(new CreateReferenceCategoryDataInput('Santé'), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankLabel(): void
    {
        try {
            $this->validator->validate(new CreateReferenceCategoryDataInput(''), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('label_required', $exception->violations['label']);
        }
    }

    public function testItRejectsALabelAnotherCommonCategoryHas(): void
    {
        try {
            $this->validator->validate(new CreateReferenceCategoryDataInput('Home'), new CategoryDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }
}
