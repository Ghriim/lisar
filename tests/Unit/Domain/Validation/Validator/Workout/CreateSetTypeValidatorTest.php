<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\Input\Workout\CreateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\CreateSetTypeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateSetTypeValidatorTest extends TestCase
{
    private CreateSetTypeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateSetTypeValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new CreateSetTypeDataInput('Dropset', 'purple'), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new CreateSetTypeDataInput('', 'purple'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate(new CreateSetTypeDataInput(str_repeat('a', 129), 'purple'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherRowCarries(): void
    {
        $other = new SetTypeDataModel();
        $other->id = 99;

        try {
            $this->validator->validate(new CreateSetTypeDataInput('Dropset', 'purple'), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItRejectsAColourOutsideThePalette(): void
    {
        try {
            $this->validator->validate(new CreateSetTypeDataInput('Dropset', '#ff0000'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('colour_unknown', $exception->violations['colour']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $other = new SetTypeDataModel();
        $other->id = 99;

        try {
            $this->validator->validate(new CreateSetTypeDataInput('Dropset', 'beige'), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['colour_unknown'], $exception->violations['colour']);
            self::assertSame([SetTypeNameAvailableConstraint::NAME_ALREADY_USED], $exception->violations['name']);
        }
    }
}
