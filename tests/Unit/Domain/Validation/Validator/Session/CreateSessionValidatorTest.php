<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Session;

use App\Domain\DTO\Input\Session\CreateSessionDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Validator\Session\CreateSessionValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateSessionValidatorTest extends TestCase
{
    private CreateSessionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateSessionValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );
    }

    public function testItAcceptsCredentials(): void
    {
        $this->validator->validate(new CreateSessionDataInput('alice@lisar.test', 'Corr3ct-Horse!'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankEmail(): void
    {
        $this->assertViolatesOn(new CreateSessionDataInput('', 'Corr3ct-Horse!'), 'email', 'email_required');
    }

    public function testItRejectsAMalformedEmail(): void
    {
        $this->assertViolatesOn(new CreateSessionDataInput('nope', 'Corr3ct-Horse!'), 'email', 'email_invalid');
    }

    public function testItRejectsABlankPassword(): void
    {
        $this->assertViolatesOn(new CreateSessionDataInput('alice@lisar.test', ''), 'password', 'password_required');
    }

    /**
     * Sign-in deliberately does not re-check the password policy: an account created before a
     * policy change must still be able to sign in.
     */
    public function testItAcceptsAPasswordThatNoLongerMeetsTheSignUpPolicy(): void
    {
        $this->validator->validate(new CreateSessionDataInput('alice@lisar.test', 'weak'));

        $this->expectNotToPerformAssertions();
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(new CreateSessionDataInput('', ''));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSessionValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('email', $exception->violations);
            self::assertArrayHasKey('password', $exception->violations);
        }
    }

    private function assertViolatesOn(CreateSessionDataInput $input, string $propertyPath, string $errorCode): void
    {
        try {
            $this->validator->validate($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateSessionValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains($errorCode, $exception->violations[$propertyPath]);
        }
    }
}
