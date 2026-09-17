<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\Input\Admin\CreateAdminDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\User\IdentityProviderRegistry;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\User\PasswordHasherInterface;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\CreateAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private CreateAdminUseCase $useCase;
    private UserProviderGateway $userProviderGateway;
    private PasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(CreateAdminUseCase::class);
        $this->userProviderGateway = self::getContainer()->get(UserProviderGateway::class);
        $this->passwordHasher = self::getContainer()->get(PasswordHasherInterface::class);
    }

    public function testItCreatesAnAdministrator(): void
    {
        $output = $this->useCase->execute($this->buildInput());

        self::assertSame('quentin', $output->username);
        self::assertTrue($output->isActive);

        // Re-read through the gateway: the role is what makes this use case worth its own name.
        $administrator = $this->userProviderGateway->findOneById($output->id);
        self::assertNotNull($administrator);
        self::assertSame(UserRoleRegistry::ADMIN, $administrator->role);

        $identity = $administrator->getIdentityForProvider(IdentityProviderRegistry::PASSWORD);
        self::assertNotNull($identity);
        self::assertNotNull($identity->passwordHash);
        self::assertTrue($this->passwordHasher->verify($identity->passwordHash, 'Str0ng-Admin!'));
    }

    public function testItRejectsAnEmailAlreadyUsed(): void
    {
        $this->useCase->execute($this->buildInput());

        try {
            $this->useCase->execute($this->buildInput(username: 'quentin2'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame([EmailAvailableConstraint::EMAIL_ALREADY_USED], $exception->violations['email']);
        }
    }

    public function testItRejectsAWeakPassword(): void
    {
        try {
            $this->useCase->execute($this->buildInput(password: 'admin'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('password', $exception->violations);
        }

        self::assertNull($this->userProviderGateway->findOneByEmail('quentin@lisar.test'));
    }

    private function buildInput(
        string $username = 'quentin',
        string $email = 'quentin@lisar.test',
        string $password = 'Str0ng-Admin!',
    ): CreateAdminDataInput {
        return new CreateAdminDataInput(username: $username, email: $email, password: $password);
    }
}
