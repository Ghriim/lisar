<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Admin;

use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\Input\Admin\CreatePriorityDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\PriorityLabelAvailableConstraint;
use App\Domain\Validation\Validator\Admin\CreatePriorityValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreatePriorityValidatorTest extends TestCase
{
    private CreatePriorityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreatePriorityValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAPriority(): void
    {
        $this->validator->validate(new CreatePriorityDataInput('Critique', 5, '#ff00aa'), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAColourThatIsNotAHexTriplet(): void
    {
        $this->assertViolatesOn(new CreatePriorityDataInput('Critique', 5, 'rouge'), 'colour', 'colour_invalid');
        $this->assertViolatesOn(new CreatePriorityDataInput('Critique', 5, '#abc'), 'colour', 'colour_invalid');
    }

    public function testItRejectsAWeightOutOfRange(): void
    {
        $this->assertViolatesOn(new CreatePriorityDataInput('Critique', -1, '#ff00aa'), 'weight', 'weight_invalid');
    }

    public function testItRejectsALabelAlreadyCarried(): void
    {
        try {
            $this->validator->validate(new CreatePriorityDataInput('High'), new PriorityDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                PriorityLabelAvailableConstraint::LABEL_ALREADY_USED,
                $exception->violations['label'],
            );
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new CreatePriorityDataInput('', 99999, 'nope'), new PriorityDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreatePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('label', $exception->violations);
            self::assertArrayHasKey('weight', $exception->violations);
            self::assertArrayHasKey('colour', $exception->violations);
        }
    }

    private function assertViolatesOn(CreatePriorityDataInput $input, string $field, string $errorCode): void
    {
        try {
            $this->validator->validate($input, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains($errorCode, $exception->violations[$field]);
        }
    }
}
