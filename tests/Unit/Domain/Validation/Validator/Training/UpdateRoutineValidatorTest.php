<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\UpdateRoutineDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\Training\UpdateRoutineValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateRoutineValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private UpdateRoutineValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateRoutineValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsANewName(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->validator->validate($this->anyCurrentUser(), $routine, new UpdateRoutineDataInput('Pull'));
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), $routine, new UpdateRoutineDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRequiresAName(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->anyCurrentUser(), $routine, new UpdateRoutineDataInput(''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['name' => ['name_required']], $exception->violations);
        }
    }
}
