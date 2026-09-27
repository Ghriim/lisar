<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\Input\Workout\UpdateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\UpdateEquipmentValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateEquipmentValidatorTest extends TestCase
{
    private UpdateEquipmentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateEquipmentValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new UpdateEquipmentDataInput('Barbell', true, false), $this->current(), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new UpdateEquipmentDataInput('', true, false), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate(new UpdateEquipmentDataInput(str_repeat('a', 129), true, false), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherRowCarries(): void
    {
        $other = new EquipmentDataModel();
        $other->id = 99;

        try {
            $this->validator->validate(new UpdateEquipmentDataInput('Barbell', true, false), $this->current(), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(EquipmentNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItLetsTheRowKeepItsOwnName(): void
    {
        $this->validator->validate(new UpdateEquipmentDataInput('Barbell', true, false), $this->current(), $this->current());

        $this->expectNotToPerformAssertions();
    }

    private function current(): EquipmentDataModel
    {
        $row = new EquipmentDataModel();
        $row->id = 1;
        $row->name = 'Barbell';

        return $row;
    }
}
