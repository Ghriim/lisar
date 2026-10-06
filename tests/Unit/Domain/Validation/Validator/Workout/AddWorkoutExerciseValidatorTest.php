<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Workout\AddWorkoutExerciseValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddWorkoutExerciseValidatorTest extends TestCase
{
    private AddWorkoutExerciseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AddWorkoutExerciseValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAnOfferedMovement(): void
    {
        $movement = new MovementDataModel();
        $movement->id = 4;

        $this->validator->validate(new AddWorkoutExerciseDataInput(4), $movement);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAMovementNotOffered(): void
    {
        try {
            $this->validator->validate(new AddWorkoutExerciseDataInput(4), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['movementId']);
        }
    }
}
