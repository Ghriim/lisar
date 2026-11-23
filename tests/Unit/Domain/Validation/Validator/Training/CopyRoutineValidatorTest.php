<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\Training\CopyRoutineValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class CopyRoutineValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CopyRoutineValidator())->validate($this->anyCurrentUser(), $this->routineOf($this->anyCurrentUser()));
    }

    public function testItRefusesSomeoneElsesRoutine(): void
    {
        try {
            (new CopyRoutineValidator())->validate($this->currentUser(99), $this->routineOf($this->anyCurrentUser()));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CopyRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
