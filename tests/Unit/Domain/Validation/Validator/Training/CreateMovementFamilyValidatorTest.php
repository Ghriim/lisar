<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\MovementFamilyNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMovementFamilyValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

use function str_repeat;

final class CreateMovementFamilyValidatorTest extends TestCase
{
    private CreateMovementFamilyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateMovementFamilyValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate(new CreateMovementFamilyDataInput('Bench press'), null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate(new CreateMovementFamilyDataInput(''), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMovementFamilyValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsATooLongName(): void
    {
        try {
            $this->validator->validate(new CreateMovementFamilyDataInput(str_repeat('a', 129)), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherFamilyCarries(): void
    {
        try {
            $this->validator->validate(new CreateMovementFamilyDataInput('Bench press'), $this->family(99));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMovementFamilyValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MovementFamilyNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    private function family(int $id): MovementFamilyDataModel
    {
        $family = new MovementFamilyDataModel();
        $family->id = $id;
        $family->name = 'Bench press';

        return $family;
    }
}
