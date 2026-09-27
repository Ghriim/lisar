<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\UpdateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\UpdateMuscleGroupValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateMuscleGroupValidatorTest extends TestCase
{
    private UpdateMuscleGroupValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateMuscleGroupValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new UpdateMuscleGroupDataInput('Chest'), $this->current(), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new UpdateMuscleGroupDataInput(''), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate(new UpdateMuscleGroupDataInput(str_repeat('a', 129)), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherRowCarries(): void
    {
        $other = new MuscleGroupDataModel();
        $other->id = 99;

        try {
            $this->validator->validate(new UpdateMuscleGroupDataInput('Chest'), $this->current(), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItLetsTheRowKeepItsOwnName(): void
    {
        $this->validator->validate(new UpdateMuscleGroupDataInput('Chest'), $this->current(), $this->current());

        $this->expectNotToPerformAssertions();
    }

    private function current(): MuscleGroupDataModel
    {
        $row = new MuscleGroupDataModel();
        $row->id = 1;
        $row->name = 'Chest';

        return $row;
    }
}
