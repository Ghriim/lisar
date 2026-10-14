<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\SetTypeNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateSetTypeValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateSetTypeValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private CreateSetTypeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateSetTypeValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }

    public function testItAcceptsAFreeName(): void
    {
        $this->validator->validate($this->currentAdmin(), new CreateSetTypeDataInput('Dropset', 'purple'), null);

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), new CreateSetTypeDataInput('Dropset', 'purple'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankName(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateSetTypeDataInput('', 'purple'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSetTypeValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    public function testItRejectsANameTooLong(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateSetTypeDataInput(str_repeat('a', 129), 'purple'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_too_long', $exception->violations['name']);
        }
    }

    public function testItRejectsANameAnotherRowCarries(): void
    {
        $other = new SetTypeDataModel();
        $other->id = 99;

        try {
            $this->validator->validate($this->currentAdmin(), new CreateSetTypeDataInput('Dropset', 'purple'), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(SetTypeNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItRejectsAColourOutsideThePalette(): void
    {
        try {
            $this->validator->validate($this->currentAdmin(), new CreateSetTypeDataInput('Dropset', '#ff0000'), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('colour_unknown', $exception->violations['colour']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $other = new SetTypeDataModel();
        $other->id = 99;

        try {
            $this->validator->validate($this->currentAdmin(), new CreateSetTypeDataInput('Dropset', 'beige'), $other);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['colour_unknown'], $exception->violations['colour']);
            self::assertSame([SetTypeNameAvailableConstraint::NAME_ALREADY_USED], $exception->violations['name']);
        }
    }
}
