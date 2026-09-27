<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Workout;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\Input\Workout\UpdateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Workout\MovementFamilyNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\UpdateMovementFamilyValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

use function str_repeat;

final class UpdateMovementFamilyValidatorTest extends TestCase
{
    private UpdateMovementFamilyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateMovementFamilyValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new UpdateMovementFamilyDataInput('Bench press'), $this->current(), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new UpdateMovementFamilyDataInput(''), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMovementFamilyValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsATooLongName(): void
    {
        try {
            $this->validator->validate(new UpdateMovementFamilyDataInput(str_repeat('a', 129)), $this->current(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherFamilyCarries(): void
    {
        try {
            $this->validator->validate(new UpdateMovementFamilyDataInput('Bench press'), $this->current(), $this->family(99));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMovementFamilyValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MovementFamilyNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testAFamilyMayKeepItsOwnName(): void
    {
        $this->validator->validate(new UpdateMovementFamilyDataInput('bench press'), $this->current(), $this->current());

        $this->expectNotToPerformAssertions();
    }

    /** Named Bench press. */
    private function current(): MovementFamilyDataModel
    {
        return $this->family(1);
    }

    private function family(int $id): MovementFamilyDataModel
    {
        $family = new MovementFamilyDataModel();
        $family->id = $id;
        $family->name = 'Bench press';

        return $family;
    }
}
