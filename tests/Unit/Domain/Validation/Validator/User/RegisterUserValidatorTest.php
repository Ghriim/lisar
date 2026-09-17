<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\User;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\User\RegisterUserDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use App\Domain\Validation\Constraint\User\UsernameAvailableConstraint;
use App\Domain\Validation\Validator\User\RegisterUserValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class RegisterUserValidatorTest extends TestCase
{
    private RegisterUserValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new RegisterUserValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsAValidSignUp(): void
    {
        $this->validator->validate($this->buildInput(), null, null);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankUsername(): void
    {
        $this->assertViolatesOn($this->buildInput(username: ''), 'username', 'username_required');
    }

    public function testItRejectsAUsernameShorterThanThreeCharacters(): void
    {
        $this->assertViolatesOn($this->buildInput(username: 'ab'), 'username', 'username_too_short');
    }

    public function testItRejectsAUsernameWithForbiddenCharacters(): void
    {
        $this->assertViolatesOn($this->buildInput(username: 'al ice!'), 'username', 'username_invalid_characters');
    }

    public function testItRejectsAMalformedEmail(): void
    {
        $this->assertViolatesOn($this->buildInput(email: 'not-an-email'), 'email', 'email_invalid');
    }

    public function testItRejectsAPasswordShorterThanEightCharacters(): void
    {
        $this->assertViolatesOn($this->buildInput(password: 'Cor3t-!'), 'password', 'password_too_short');
    }

    public function testItRejectsAPasswordWithoutALowercaseLetter(): void
    {
        $this->assertViolatesOn($this->buildInput(password: 'CORR3CT-HORSE!'), 'password', 'password_missing_lowercase');
    }

    public function testItRejectsAPasswordWithoutAnUppercaseLetter(): void
    {
        $this->assertViolatesOn($this->buildInput(password: 'corr3ct-horse!'), 'password', 'password_missing_uppercase');
    }

    public function testItRejectsAPasswordWithoutADigit(): void
    {
        $this->assertViolatesOn($this->buildInput(password: 'Correct-Horse!'), 'password', 'password_missing_digit');
    }

    public function testItRejectsAPasswordWithoutASpecialCharacter(): void
    {
        $this->assertViolatesOn($this->buildInput(password: 'Corr3ctHorse'), 'password', 'password_missing_special_character');
    }

    public function testItRejectsAnEmailAlreadyUsed(): void
    {
        try {
            $this->validator->validate($this->buildInput(), new UserDataModel(), null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
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
                new RegisterUserDataInput(username: '', email: 'nope', password: 'short'),
                new UserDataModel(),
                new UserDataModel(),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(RegisterUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('username', $exception->violations);
            self::assertArrayHasKey('email', $exception->violations);
            self::assertArrayHasKey('password', $exception->violations);
        }
    }

    private function assertViolatesOn(RegisterUserDataInput $input, string $propertyPath, string $errorCode): void
    {
        try {
            $this->validator->validate($input, null, null);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(RegisterUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey($propertyPath, $exception->violations);
            self::assertContains($errorCode, $exception->violations[$propertyPath]);
        }
    }

    private function buildInput(
        string $username = 'alice',
        string $email = 'alice@lisar.test',
        string $password = 'Corr3ct-Horse!',
    ): RegisterUserDataInput {
        return new RegisterUserDataInput(username: $username, email: $email, password: $password);
    }
}
