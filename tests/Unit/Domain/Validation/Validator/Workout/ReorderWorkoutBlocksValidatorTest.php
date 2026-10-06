<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\WorkoutDataModel;
use App\Domain\DTO\Input\Workout\ReorderWorkoutBlocksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutBlockOrderConstraint;
use App\Domain\Validation\Validator\Workout\ReorderWorkoutBlocksValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ReorderWorkoutBlocksValidatorTest extends TestCase
{
    private ReorderWorkoutBlocksValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ReorderWorkoutBlocksValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsEveryBlockInANewOrder(): void
    {
        $this->validator->validate(new ReorderWorkoutBlocksDataInput([2, 1]), $this->workout());

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsAnOrderThatIsNotTheWorkoutsBlocks(): void
    {
        try {
            $this->validator->validate(new ReorderWorkoutBlocksDataInput([2]), $this->workout());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ReorderWorkoutBlocksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutBlockOrderConstraint::MISMATCH], $exception->violations['blockIds']);
        }
    }

    private function workout(): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        foreach ([1, 2] as $id) {
            $block = new WorkoutBlockDataModel();
            $block->id = $id;
            $workout->blocks->add($block);
        }

        return $workout;
    }
}
