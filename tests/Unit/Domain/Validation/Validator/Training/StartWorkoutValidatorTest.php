<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\Training\StartWorkoutValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class StartWorkoutValidatorTest extends TestCase
{
    private StartWorkoutValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new StartWorkoutValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAStartWithNothingInProgress(): void
    {
        $this->validator->validate(new StartWorkoutDataInput('Push'), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate(new StartWorkoutDataInput(str_repeat('a', 129)), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(StartWorkoutValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['name_too_long'], $exception->violations['name']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new StartWorkoutDataInput(str_repeat('a', 129)), new WorkoutDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['name_too_long'], $exception->violations['name']);
            self::assertSame([WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS], $exception->violations['workout']);
        }
    }
}
