<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\Input\Training\CreateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateEquipmentValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateEquipmentValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private CreateEquipmentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateEquipmentValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate($this->currentAdmin(), new CreateEquipmentDataInput('Barbell', true, false), null);

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateEquipmentDataInput('Barbell', true, false), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateEquipmentDataInput('', true, false), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateEquipmentDataInput(str_repeat('a', 129), true, false), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherRowCarries(): void
    {
        $other = new EquipmentDataModel();
        $other->id = 99;

        try {
            $this->validator->validate($this->currentAdmin(), new CreateEquipmentDataInput('Barbell', true, false), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(EquipmentNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }
}
