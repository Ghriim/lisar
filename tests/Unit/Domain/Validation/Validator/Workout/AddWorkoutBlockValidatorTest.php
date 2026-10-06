<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\Input\Workout\AddWorkoutBlockDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Workout\AddWorkoutBlockValidator;
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
        $this->validator->validate(new AddWorkoutBlockDataInput([1, 2]), [$this->movement(1), $this->movement(2)]);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlockWithoutAMovement(): void
    {
        $this->assertRefused(new AddWorkoutBlockDataInput([]), [], 'movement_ids_required');
    }

    public function testItRejectsTooManyMovements(): void
    {
        $ids = range(1, AddWorkoutBlockDataInput::MAX_MOVEMENTS + 1);

        $this->assertRefused(new AddWorkoutBlockDataInput($ids), array_map(fn (int $id) => $this->movement($id), $ids), 'movement_ids_too_many');
    }

    public function testItRejectsAMovementTwiceInOneSuperset(): void
    {
        $this->assertRefused(new AddWorkoutBlockDataInput([1, 1]), [$this->movement(1)], 'movement_ids_duplicated');
    }

    public function testItRejectsAMovementNotOffered(): void
    {
        $this->assertRefused(new AddWorkoutBlockDataInput([1, 2]), [$this->movement(1)], WorkoutMovementsOfferedConstraint::UNAVAILABLE);
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new AddWorkoutBlockDataInput([1, 1]), []);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['movement_ids_duplicated', WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['movementIds']);
        }
    }

    /** @param list<MovementDataModel> $offered */
    private function assertRefused(AddWorkoutBlockDataInput $input, array $offered, string $code): void
    {
        try {
            $this->validator->validate($input, $offered);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($code, $exception->violations['movementIds']);
        }
    }

    private function movement(int $id): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;

        return $movement;
    }
}
