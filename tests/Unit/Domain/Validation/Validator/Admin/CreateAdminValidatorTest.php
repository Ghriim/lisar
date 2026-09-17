<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Admin\CreateAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use App\Domain\Validation\Constraint\User\UsernameAvailableConstraint;
use App\Domain\Validation\Validator\Admin\CreateAdminValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateAdminValidatorTest extends TestCase
{
    private CreateAdminValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateAdminValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAValidAdministrator(): void
    {
        $this->validator->validate($this->buildInput(), null, null);

        $this->expectNotToPerformAssertions();
    }

    /**
     * An administrator's password is not exempt from the sign-up policy.
     */
    public function testItHoldsAdministratorsToThePasswordPolicy(): void
    {
        try {
            $this->validator->validate($this->buildInput(password: 'admin'), null, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('password_too_short', $exception->violations['password']);
            self::assertContains('password_missing_uppercase', $exception->violations['password']);
            self::assertContains('password_missing_digit', $exception->violations['password']);
            self::assertContains('password_missing_special_character', $exception->violations['password']);
        }
    }

    public function testItRejectsAnEmailAlreadyUsed(): void
    {
        try {
            $this->validator->validate($this->buildInput(), new UserDataModel(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateAdminValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([EmailAvailableConstraint::EMAIL_ALREADY_USED], $exception->violations['email']);
        }
    }

    public function testItRejectsAUsernameAlreadyUsed(): void
    {
        try {
            $this->validator->validate($this->buildInput(), null, new UserDataModel());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([UsernameAvailableConstraint::USERNAME_ALREADY_USED], $exception->violations['username']);
        }
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(
                new CreateAdminDataInput(username: '', email: 'nope', password: 'weak'),
                new UserDataModel(),
                new UserDataModel(),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('username', $exception->violations);
            self::assertArrayHasKey('email', $exception->violations);
            self::assertArrayHasKey('password', $exception->violations);
        }
    }

    private function buildInput(
        string $username = 'quentin',
        string $email = 'quentin@lisar.test',
        string $password = 'Str0ng-Admin!',
    ): CreateAdminDataInput {
        return new CreateAdminDataInput(username: $username, email: $email, password: $password);
    }
}
