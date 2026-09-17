<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User;

use App\Domain\DTO\Input\User\RegisterUserDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\User\IdentityProviderRegistry;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use App\Domain\Validation\Constraint\User\UsernameAvailableConstraint;
use App\Domain\Validation\Validator\User\RegisterUserValidator;
use App\UseCase\User\RegisterUserUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterUserUseCaseTest extends KernelTestCase
{
    private RegisterUserUseCase $useCase;
    private UserProviderGateway $userProviderGateway;
    private PasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(RegisterUserUseCase::class);
        $this->userProviderGateway = self::getContainer()->get(UserProviderGateway::class);
        $this->passwordHasher = self::getContainer()->get(PasswordHasherInterface::class);
    }

    public function testItCreatesAnAccount(): void
    {
        $output = $this->useCase->execute($this->buildInput());

        self::assertSame('alice', $output->username);
        self::assertSame('alice@lisar.test', $output->email);
        self::assertTrue($output->isActive);
        self::assertNull($output->lastSignedInAt);
        self::assertNotNull($output->createdAt);

        // Re-read through the gateway: assert it was really persisted.
        $user = $this->userProviderGateway->findOneById($output->id);
        self::assertNotNull($user);
        self::assertSame('alice', $user->username);
        self::assertSame(UserRoleRegistry::USER, $user->role);
        self::assertTrue($user->isActive);
    }

    public function testItStoresThePasswordAsAHashOnly(): void
    {
        $output = $this->useCase->execute($this->buildInput());

        $user = $this->userProviderGateway->findOneById($output->id);
        self::assertNotNull($user);

        $identity = $user->getIdentityForProvider(IdentityProviderRegistry::PASSWORD);
        self::assertNotNull($identity);
        self::assertNotNull($identity->passwordHash);
        self::assertNotSame('Corr3ct-Horse!', $identity->passwordHash);
        self::assertTrue($this->passwordHasher->verify($identity->passwordHash, 'Corr3ct-Horse!'));
        self::assertNull($identity->externalId);
    }

    public function testItRejectsAnEmailAlreadyUsed(): void
    {
        $this->useCase->execute($this->buildInput());

        try {
            $this->useCase->execute($this->buildInput(username: 'alice2'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(RegisterUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([EmailAvailableConstraint::EMAIL_ALREADY_USED], $exception->violations['email']);
        }
    }

    public function testItRejectsAUsernameAlreadyUsed(): void
    {
        $this->useCase->execute($this->buildInput());

        try {
            $this->useCase->execute($this->buildInput(email: 'alice2@lisar.test'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(RegisterUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([UsernameAvailableConstraint::USERNAME_ALREADY_USED], $exception->violations['username']);
        }
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        try {
            $this->useCase->execute(new RegisterUserDataInput(username: '', email: 'nope', password: 'short'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('username', $exception->violations);
            self::assertArrayHasKey('email', $exception->violations);
            self::assertArrayHasKey('password', $exception->violations);
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
