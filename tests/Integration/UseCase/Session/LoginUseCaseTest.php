<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Session;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\Exception\AccountDeactivatedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\ValidationException;
use App\Domain\Exception\WrongAudienceException;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Session\SessionAudienceRegistry;
use App\Domain\Session\RefreshTokenGenerator;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Session\LoginUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use const DATE_ATOM;

final class LoginUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private LoginUseCase $useCase;
    private SessionProviderGateway $sessionProviderGateway;
    private UserProviderGateway $userProviderGateway;
    private RefreshTokenGenerator $refreshTokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(LoginUseCase::class);
        $this->sessionProviderGateway = self::getContainer()->get(SessionProviderGateway::class);
        $this->userProviderGateway = self::getContainer()->get(UserProviderGateway::class);
        $this->refreshTokenGenerator = self::getContainer()->get(RefreshTokenGenerator::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItOpensASession(): void
    {
        $output = $this->useCase->execute($this->buildInput(), SessionAudienceRegistry::WEBSITE);

        self::assertNotSame('', $output->accessToken);
        self::assertSame(900, $output->expiresIn);
        self::assertSame('Bearer', $output->tokenType);
        self::assertNotSame('', $output->refreshToken);

        // Re-read through the gateway: assert the session was really persisted, as a hash.
        $session = $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($output->refreshToken),
        );
        self::assertNotNull($session);
        self::assertNull($session->revokedAt);
        self::assertSame('alice@lisar.test', $session->user->email);
        self::assertSame($output->refreshTokenExpiresAt->format(DATE_ATOM), $session->expiresAt->format(DATE_ATOM));
    }

    public function testItStampsTheLastSignIn(): void
    {
        $user = $this->userProviderGateway->findOneByEmail('alice@lisar.test');
        self::assertNotNull($user);
        self::assertNull($user->lastSignedInAt);

        $this->useCase->execute($this->buildInput(), SessionAudienceRegistry::WEBSITE);

        $user = $this->userProviderGateway->findOneByEmail('alice@lisar.test');
        self::assertNotNull($user);
        self::assertNotNull($user->lastSignedInAt);
    }

    public function testItOpensOneSessionPerSignIn(): void
    {
        $first = $this->useCase->execute($this->buildInput(), SessionAudienceRegistry::WEBSITE);
        $second = $this->useCase->execute($this->buildInput(), SessionAudienceRegistry::WEBSITE);

        self::assertNotSame($first->refreshToken, $second->refreshToken);

        $user = $this->userProviderGateway->findOneByEmail('alice@lisar.test');
        self::assertNotNull($user);
        self::assertCount(2, $this->sessionProviderGateway->findAllLiveForUser($user));
    }

    public function testItRejectsAWrongPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute($this->buildInput(password: 'Wr0ng-Password!'), SessionAudienceRegistry::WEBSITE);
    }

    public function testItRejectsAnUnknownEmailTheSameWayAsAWrongPassword(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute($this->buildInput(email: 'nobody@lisar.test'), SessionAudienceRegistry::WEBSITE);
    }

    public function testItRefusesADeactivatedAccount(): void
    {
        $this->expectException(AccountDeactivatedException::class);

        $this->useCase->execute($this->buildInput(email: 'bob@lisar.test'), SessionAudienceRegistry::WEBSITE);
    }

    public function testItOpensNoSessionForADeactivatedAccount(): void
    {
        try {
            $this->useCase->execute($this->buildInput(email: 'bob@lisar.test'), SessionAudienceRegistry::WEBSITE);
        } catch (AccountDeactivatedException) {
            // Expected; what matters is what was not written.
        }

        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($bob));
    }

    public function testItOpensAnAdminSessionForAnAdministrator(): void
    {
        $output = $this->useCase->execute(
            $this->buildInput(email: 'admin@lisar.test'),
            SessionAudienceRegistry::ADMIN,
        );

        self::assertNotSame('', $output->accessToken);
    }

    public function testItRefusesAnAdministratorOnTheWebsite(): void
    {
        $this->expectException(WrongAudienceException::class);

        $this->useCase->execute($this->buildInput(email: 'admin@lisar.test'), SessionAudienceRegistry::WEBSITE);
    }

    public function testItRefusesAUserInTheBackOffice(): void
    {
        $this->expectException(WrongAudienceException::class);

        $this->useCase->execute($this->buildInput(), SessionAudienceRegistry::ADMIN);
    }

    public function testItOpensNoSessionForTheWrongAudience(): void
    {
        try {
            $this->useCase->execute($this->buildInput(), SessionAudienceRegistry::ADMIN);
        } catch (WrongAudienceException) {
            // Expected; what matters is what was not written.
        }

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($alice));
    }

    public function testItRejectsAnInvalidPayload(): void
    {
        try {
            $this->useCase->execute(new LoginDataInput(email: '', password: ''), SessionAudienceRegistry::WEBSITE);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('email', $exception->violations);
            self::assertArrayHasKey('password', $exception->violations);
        }
    }

    private function buildInput(
        string $email = 'alice@lisar.test',
        string $password = UserFixtures::PLAIN_PASSWORD,
    ): LoginDataInput {
        return new LoginDataInput(email: $email, password: $password);
    }
}
