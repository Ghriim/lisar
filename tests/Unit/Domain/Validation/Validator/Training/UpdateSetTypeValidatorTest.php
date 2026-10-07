<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\UpdateSetTypeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateSetTypeValidatorTest extends TestCase
{
    private UpdateSetTypeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateSetTypeValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new UpdateSetTypeDataInput('Dropset', 'purple'), $this->current(), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new UpdateSetTypeDataInput('', 'purple'), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate(new UpdateSetTypeDataInput(str_repeat('a', 129), 'purple'), $this->current(), null);
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
            $this->validator->validate(new UpdateSetTypeDataInput('Dropset', 'purple'), $this->current(), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItLetsTheRowKeepItsOwnName(): void
    {
        $this->validator->validate(new UpdateSetTypeDataInput('Dropset', 'purple'), $this->current(), $this->current());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAColourOutsideThePalette(): void
    {
        try {
            $this->validator->validate(new UpdateSetTypeDataInput('Dropset', '#ff0000'), $this->current(), null);
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
            $this->validator->validate(new UpdateSetTypeDataInput('Dropset', 'beige'), $this->current(), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['colour_unknown'], $exception->violations['colour']);
            self::assertSame([SetTypeNameAvailableConstraint::NAME_ALREADY_USED], $exception->violations['name']);
        }
    }

    private function current(): SetTypeDataModel
    {
        $row = new SetTypeDataModel();
        $row->id = 1;
        $row->name = 'Dropset';

        return $row;
    }
}
