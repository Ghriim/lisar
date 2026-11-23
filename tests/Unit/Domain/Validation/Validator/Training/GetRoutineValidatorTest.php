<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\Training\GetRoutineValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class GetRoutineValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private GetRoutineValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new GetRoutineValidator();
    }

    public function testTheOwnerPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine);
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(GetRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** Owned by no one — shared from the back-office — is no caller's to act on as an owner. */
    public function testItRefusesARoutineOwnedByNoOne(): void
    {
        $routine = $this->routineOf(null);

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(GetRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
