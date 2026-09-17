<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Session;

use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Session\LoginValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class LoginValidatorTest extends TestCase
{
    private LoginValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new LoginValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsCredentials(): void
    {
        $this->validator->validate(new LoginDataInput('alice@lisar.test', 'Corr3ct-Horse!'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankEmail(): void
    {
        $this->assertViolatesOn(new LoginDataInput('', 'Corr3ct-Horse!'), 'email', 'email_required');
    }

    public function testItRejectsAMalformedEmail(): void
    {
        $this->assertViolatesOn(new LoginDataInput('nope', 'Corr3ct-Horse!'), 'email', 'email_invalid');
    }

    public function testItRejectsABlankPassword(): void
    {
        $this->assertViolatesOn(new LoginDataInput('alice@lisar.test', ''), 'password', 'password_required');
    }

    /**
     * Sign-in deliberately does not re-check the password policy: an account created before a
     * policy change must still be able to sign in.
     */
    public function testItAcceptsAPasswordThatNoLongerMeetsTheSignUpPolicy(): void
    {
        $this->validator->validate(new LoginDataInput('alice@lisar.test', 'weak'));

        $this->expectNotToPerformAssertions();
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new LoginDataInput('', ''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(LoginValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('email', $exception->violations);
            self::assertArrayHasKey('password', $exception->violations);
        }
    }

    private function assertViolatesOn(LoginDataInput $input, string $propertyPath, string $errorCode): void
    {
        try {
            $this->validator->validate($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(LoginValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($errorCode, $exception->violations[$propertyPath]);
        }
    }
}
