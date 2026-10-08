<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Training\AddWorkoutBlockValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class AddWorkoutBlockValidatorTest extends TestCase
{
    private AddWorkoutBlockValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AddWorkoutBlockValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsASupersetOfOfferedMovements(): void
    {
        $this->validator->validate($this->block(1, 2), [$this->movement(1), $this->movement(2)]);

        $this->expectNotToPerformAssertions();
    }

    public function testItAcceptsARestOnEachMovement(): void
    {
        $input = new AddWorkoutBlockDataInput([
            new AddWorkoutBlockExerciseDataInput(1, 90),
            new AddWorkoutBlockExerciseDataInput(2, 3600),
        ]);

        $this->validator->validate($input, [$this->movement(1), $this->movement(2)]);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlockWithoutAMovement(): void
    {
        $this->assertRefused(new AddWorkoutBlockDataInput([]), [], 'exercises', 'exercises_required');
    }

    public function testItRejectsTooManyMovements(): void
    {
        $ids = range(1, AddWorkoutBlockDataInput::MAX_MOVEMENTS + 1);

        $this->assertRefused($this->block(...$ids), array_map(fn (int $id) => $this->movement($id), $ids), 'exercises', 'exercises_too_many');
    }

    public function testItRejectsAMovementTwiceInOneSuperset(): void
    {
        $this->assertRefused($this->block(1, 1), [$this->movement(1)], 'exercises', 'movement_ids_duplicated');
    }

    public function testItRejectsAMovementNotOffered(): void
    {
        $this->assertRefused($this->block(1, 2), [$this->movement(1)], 'exercises', WorkoutMovementsOfferedConstraint::UNAVAILABLE);
    }

    public function testItRejectsARestOutOfBounds(): void
    {
        $input = new AddWorkoutBlockDataInput([
            new AddWorkoutBlockExerciseDataInput(1, 0),
            new AddWorkoutBlockExerciseDataInput(2, 3601),
        ]);

        try {
            $this->validator->validate($input, [$this->movement(1), $this->movement(2)]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['rest_invalid'], $exception->violations['exercises[0].restInSeconds']);
            self::assertSame(['rest_invalid'], $exception->violations['exercises[1].restInSeconds']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate($this->block(1, 1), []);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['movement_ids_duplicated', WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['exercises']);
        }
    }

    /** @param list<MovementDataModel> $offered */
    private function assertRefused(AddWorkoutBlockDataInput $input, array $offered, string $field, string $code): void
    {
        try {
            $this->validator->validate($input, $offered);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($code, $exception->violations[$field]);
        }
    }

    /** One movement per id, none with a rest. */
    private function block(int ...$movementIds): AddWorkoutBlockDataInput
    {
        return new AddWorkoutBlockDataInput(array_map(static fn (int $id) => new AddWorkoutBlockExerciseDataInput($id), $movementIds));
    }

    private function movement(int $id): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;

        return $movement;
    }
}
