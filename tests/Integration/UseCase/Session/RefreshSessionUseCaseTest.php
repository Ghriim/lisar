<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Session;

use App\Domain\DTO\DataModel\SessionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Session\CreateSessionDataInput;
use App\Domain\DTO\Output\Session\SessionDataOutput;
use App\Domain\Exception\AccountDeactivatedException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Gateway\Persister\UserPersisterGateway;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Session\RefreshTokenGenerator;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Session\CreateSessionUseCase;
use App\UseCase\Session\RefreshSessionUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RefreshSessionUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private RefreshSessionUseCase $useCase;
    private CreateSessionUseCase $createSessionUseCase;
    private SessionProviderGateway $sessionProviderGateway;
    private UserProviderGateway $userProviderGateway;
    private UserPersisterGateway $userPersisterGateway;
    private RefreshTokenGenerator $refreshTokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(RefreshSessionUseCase::class);
        $this->createSessionUseCase = self::getContainer()->get(CreateSessionUseCase::class);
        $this->sessionProviderGateway = self::getContainer()->get(SessionProviderGateway::class);
        $this->userProviderGateway = self::getContainer()->get(UserProviderGateway::class);
        $this->userPersisterGateway = self::getContainer()->get(UserPersisterGateway::class);
        $this->refreshTokenGenerator = self::getContainer()->get(RefreshTokenGenerator::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItRotatesTheSession(): void
    {
        $signedIn = $this->signIn();

        $refreshed = $this->useCase->execute($signedIn->refreshToken);

        self::assertNotSame($signedIn->refreshToken, $refreshed->refreshToken);
        self::assertNotSame('', $refreshed->accessToken);

        // The spent token is revoked, the new one is live: one usable session, not two.
        $spent = $this->findSession($signedIn->refreshToken);
        self::assertNotNull($spent);
        self::assertNotNull($spent->revokedAt);

        $live = $this->findSession($refreshed->refreshToken);
        self::assertNotNull($live);
        self::assertNull($live->revokedAt);

        self::assertCount(1, $this->sessionProviderGateway->findAllLiveForUser($live->user));
    }

    public function testItRejectsATokenThatWasAlreadySpent(): void
    {
        $signedIn = $this->signIn();
        $this->useCase->execute($signedIn->refreshToken);

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute($signedIn->refreshToken);
    }

    public function testItKillsEverySessionOfTheAccountWhenATokenIsReplayed(): void
    {
        $signedIn = $this->signIn();
        $refreshed = $this->useCase->execute($signedIn->refreshToken);

        try {
            // Replaying a spent token: we cannot tell the legitimate holder from a thief.
            $this->useCase->execute($signedIn->refreshToken);
        } catch (InvalidCredentialsException) {
            // Expected; what matters is the state it left behind.
        }

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($alice));

        // Including the one that had just been handed out.
        $this->expectException(InvalidCredentialsException::class);
        $this->useCase->execute($refreshed->refreshToken);
    }

    public function testItRejectsAnUnknownToken(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute('not-a-refresh-token');
    }

    public function testItRejectsAnEmptyToken(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->useCase->execute('');
    }

    public function testItRefusesAnAccountDeactivatedSinceTheSignIn(): void
    {
        $signedIn = $this->signIn();

        $alice = $this->userProviderGateway->findOneByEmail('alice@lisar.test');
        self::assertNotNull($alice);
        $alice->isActive = false;
        $this->userPersisterGateway->update($alice);

        $this->expectException(AccountDeactivatedException::class);

        $this->useCase->execute($signedIn->refreshToken);
    }

    private function signIn(): SessionDataOutput
    {
        return $this->createSessionUseCase->execute(new CreateSessionDataInput(
            email: 'alice@lisar.test',
            password: UserFixtures::PLAIN_PASSWORD,
        ));
    }

    private function findSession(string $refreshToken): ?SessionDataModel
    {
        return $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($refreshToken),
        );
    }
}
