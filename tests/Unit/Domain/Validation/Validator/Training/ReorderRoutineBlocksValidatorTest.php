<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\Input\Training\ReorderRoutineBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\RoutineBlockOrderConstraint;
use App\Domain\Validation\Validator\Training\ReorderRoutineBlocksValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ReorderRoutineBlocksValidatorTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    private ReorderRoutineBlocksValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ReorderRoutineBlocksValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsEveryBlockInANewOrder(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        $this->routineBlockIn($routine, 0, 1);
        $this->routineBlockIn($routine, 1, 2);
        $this->validator->validate($this->anyCurrentUser(), new ReorderRoutineBlocksDataInput([2, 1]), $routine);
    }

    /** Someone else's routine: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesRoutine(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());

        try {
            $this->validator->validate($this->currentUser(99), new ReorderRoutineBlocksDataInput([9]), $routine);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReorderRoutineBlocksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsAListThatIsNotTheRoutinesBlocks(): void
    {
        $routine = $this->routineOf($this->anyCurrentUser());
        $this->routineBlockIn($routine, 0, 1);

        try {
            $this->validator->validate($this->anyCurrentUser(), new ReorderRoutineBlocksDataInput([1, 9]), $routine);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReorderRoutineBlocksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['blockIds' => [RoutineBlockOrderConstraint::MISMATCH]], $exception->violations);
        }
    }
}
