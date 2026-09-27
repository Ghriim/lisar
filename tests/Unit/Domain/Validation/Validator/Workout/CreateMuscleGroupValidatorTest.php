<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\CreateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\CreateMuscleGroupValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateMuscleGroupValidatorTest extends TestCase
{
    private CreateMuscleGroupValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateMuscleGroupValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new CreateMuscleGroupDataInput('Chest'), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new CreateMuscleGroupDataInput(''), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate(new CreateMuscleGroupDataInput(str_repeat('a', 129)), null);
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
            $this->validator->validate(new CreateMuscleGroupDataInput('Chest'), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }
}
