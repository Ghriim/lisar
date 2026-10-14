<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\Training\GetWorkoutStatsValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class GetWorkoutStatsValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsTheCallersOwnWorkoutThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new GetWorkoutStatsValidator())->validate($this->currentUser(1), $this->workoutOf($this->currentUser(1)));
    }

    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            (new GetWorkoutStatsValidator())->validate($this->currentUser(1), $this->workoutOf($this->currentUser(2)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(GetWorkoutStatsValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
