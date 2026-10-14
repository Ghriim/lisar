<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Training\AddWorkoutExerciseValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddWorkoutExerciseValidatorTest extends TestCase
{
    use CurrentUserTrait;

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

        $this->validator->validate($this->anyCurrentUser(), $this->workoutOf($this->anyCurrentUser()), new AddWorkoutExerciseDataInput(4), $movement);

        $this->expectNotToPerformAssertions();
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $this->validator->validate($this->currentUser(99), $this->workoutOf($this->anyCurrentUser()), new AddWorkoutExerciseDataInput(4), $movement);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsAMovementNotOffered(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), $this->workoutOf($this->anyCurrentUser()), new AddWorkoutExerciseDataInput(4), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['movementId']);
        }
    }

    public function testItRejectsARestOutOfBounds(): void
    {
        $movement = new MovementDataModel();
        $movement->id = 4;

        try {
            $this->validator->validate($this->anyCurrentUser(), $this->workoutOf($this->anyCurrentUser()), new AddWorkoutExerciseDataInput(4, 0), $movement);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['rest_invalid'], $exception->violations['restInSeconds']);
        }
    }
}
