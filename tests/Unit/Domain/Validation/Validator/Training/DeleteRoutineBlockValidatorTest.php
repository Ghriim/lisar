<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockInRoutineConstraint;
use App\Domain\Validation\Validator\Training\DeleteRoutineBlockValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class DeleteRoutineBlockValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private DeleteRoutineBlockValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new DeleteRoutineBlockValidator();
    }

    public function testOneOfItsOwnBlocksPasses(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineBlockIn($routine));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, $this->routineBlockIn($routine));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    /** One of another of the caller's routines is answered like one that does not exist, and alone. */
    public function testItRefusesOneOfAnotherRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $other = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, $this->routineBlockIn($other));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['blockId' => [RoutineBlockInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }
}
