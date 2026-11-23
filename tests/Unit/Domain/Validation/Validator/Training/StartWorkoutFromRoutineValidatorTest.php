<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineGivesSetsConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutNotInProgressConstraint;
use App\Domain\Validation\Validator\Training\StartWorkoutFromRoutineValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class StartWorkoutFromRoutineValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new StartWorkoutFromRoutineValidator())->validate($this->anyCurrentUser(), $this->routineOf($this->anyCurrentUser()), null, 3);
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $this->assertRefused(
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new StartWorkoutFromRoutineValidator())->validate($this->currentUser(99), $this->routineOf($this->anyCurrentUser()), new WorkoutDataModel(), 0),
        );
    }

    /** A routine owned by no one — shared from the back-office — is not started from here yet. */
    public function testItRefusesARoutineOwnedByNoOne(): void
    {
        $this->assertRefused(
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new StartWorkoutFromRoutineValidator())->validate($this->anyCurrentUser(), $this->routineOf(null), null, 3),
        );
    }

    public function testItRefusesWhileAWorkoutIsInProgress(): void
    {
        $this->assertRefused(
            ['workout' => [WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS]],
            fn () => (new StartWorkoutFromRoutineValidator())->validate($this->anyCurrentUser(), $this->routineOf($this->anyCurrentUser()), new WorkoutDataModel(), 3),
        );
    }

    public function testItRefusesARoutineGivingNoSet(): void
    {
        $this->assertRefused(
            ['routine' => [RoutineGivesSetsConstraint::EMPTY_ROUTINE]],
            fn () => (new StartWorkoutFromRoutineValidator())->validate($this->anyCurrentUser(), $this->routineOf($this->anyCurrentUser()), null, 0),
        );
    }

    public function testItAccumulatesTheViolations(): void
    {
        $this->assertRefused(
            ['workout' => [WorkoutNotInProgressConstraint::ALREADY_IN_PROGRESS], 'routine' => [RoutineGivesSetsConstraint::EMPTY_ROUTINE]],
            fn () => (new StartWorkoutFromRoutineValidator())->validate($this->anyCurrentUser(), $this->routineOf($this->anyCurrentUser()), new WorkoutDataModel(), 0),
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(StartWorkoutFromRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
