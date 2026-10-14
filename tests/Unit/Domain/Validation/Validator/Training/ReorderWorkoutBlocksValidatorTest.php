<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\Input\Training\ReorderWorkoutBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutBlockOrderConstraint;
use App\Domain\Validation\Validator\Training\ReorderWorkoutBlocksValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ReorderWorkoutBlocksValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private ReorderWorkoutBlocksValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ReorderWorkoutBlocksValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsEveryBlockInANewOrder(): void
    {
        $this->validator->validate($this->anyCurrentUser(), new ReorderWorkoutBlocksDataInput([2, 1]), $this->workout());

        $this->expectNotToPerformAssertions();
    }

    /** Someone else's workout: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesWorkout(): void
    {
        try {
            $this->validator->validate($this->currentUser(99), new ReorderWorkoutBlocksDataInput([2, 1]), $this->workout());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReorderWorkoutBlocksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsAnOrderThatIsNotTheWorkoutsBlocks(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new ReorderWorkoutBlocksDataInput([2]), $this->workout());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReorderWorkoutBlocksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutBlockOrderConstraint::MISMATCH], $exception->violations['blockIds']);
        }
    }

    private function workout(): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->owner = $this->anyCurrentUser();
        foreach ([1, 2] as $id) {
            $block = new WorkoutBlockDataModel();
            $block->id = $id;
            $workout->blocks->add($block);
        }

        return $workout;
    }
}
