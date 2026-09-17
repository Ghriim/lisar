<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\Input\Task\CreateCategoryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryLabelAvailableConstraint;
use App\Domain\Validation\Validator\Task\CreateCategoryValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateCategoryValidatorTest extends TestCase
{
    private CreateCategoryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateCategoryValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAFreeLabel(): void
    {
        $this->validator->validate(new CreateCategoryDataInput('Sport'), null, null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankLabel(): void
    {
        try {
            $this->validator->validate(new CreateCategoryDataInput(''), null, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('label_required', $exception->violations['label']);
        }
    }

    public function testItRejectsALabelAlreadyUsed(): void
    {
        try {
            $this->validator->validate(new CreateCategoryDataInput('Home'), new CategoryDataModel(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                CategoryLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }
}
