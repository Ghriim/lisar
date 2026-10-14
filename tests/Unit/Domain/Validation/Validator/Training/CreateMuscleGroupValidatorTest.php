<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMuscleGroupValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateMuscleGroupValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private CreateMuscleGroupValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateMuscleGroupValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate($this->currentAdmin(), new CreateMuscleGroupDataInput('Chest'), null);

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateMuscleGroupDataInput('Chest'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateMuscleGroupDataInput(''), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateMuscleGroupDataInput(str_repeat('a', 129)), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherRowCarries(): void
    {
        $other = new MuscleGroupDataModel();
        $other->id = 99;

        try {
            $this->validator->validate($this->currentAdmin(), new CreateMuscleGroupDataInput('Chest'), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }
}
