<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\User\LoginDataInput;
use App\Domain\DTO\Output\User\SessionDataOutput;
use App\Domain\Gateway\Provider\User\SessionProviderGateway;
use App\Domain\Registry\User\SessionAudienceRegistry;
use App\Domain\User\RefreshTokenGenerator;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\User\LoginUseCase;
use App\UseCase\User\LogoutUseCase;
use App\UseCase\User\RefreshSessionUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LogoutUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private LogoutUseCase $useCase;
    private LoginUseCase $createSessionUseCase;
    private SessionProviderGateway $sessionProviderGateway;
    private RefreshTokenGenerator $refreshTokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(LogoutUseCase::class);
        $this->createSessionUseCase = self::getContainer()->get(LoginUseCase::class);
        $this->sessionProviderGateway = self::getContainer()->get(SessionProviderGateway::class);
        $this->refreshTokenGenerator = self::getContainer()->get(RefreshTokenGenerator::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItRevokesTheSession(): void
    {
        $signedIn = $this->signIn();

        $this->useCase->execute($signedIn->refreshToken);

        $session = $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($signedIn->refreshToken),
        );
        self::assertNotNull($session);
        self::assertNotNull($session->revokedAt);

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($alice));
    }

    public function testItSignsTheAccountOutOfEveryDevice(): void
    {
        $onePhone = $this->signIn();
        $anotherDevice = $this->signIn();

        $this->useCase->execute($onePhone->refreshToken);

        $other = $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($anotherDevice->refreshToken),
        );
        self::assertNotNull($other);
        self::assertNotNull($other->revokedAt);

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($alice));
    }

    public function testItStillSignsOutWhenTheTokenWasAlreadyRotated(): void
    {
        $signedIn = $this->signIn();
        $refreshed = self::getContainer()->get(RefreshSessionUseCase::class)->execute($signedIn->refreshToken, SessionAudienceRegistry::WEBSITE);

        // The caller hands back the token it had before the silent refresh landed.
        $this->useCase->execute($signedIn->refreshToken);

        $current = $this->sessionProviderGateway->findOneByRefreshTokenHash(
            $this->refreshTokenGenerator->hash($refreshed->refreshToken),
        );
        self::assertNotNull($current);
        self::assertNotNull($current->revokedAt);
    }

    public function testItIsIdempotent(): void
    {
        $signedIn = $this->signIn();

        $this->useCase->execute($signedIn->refreshToken);
        $this->useCase->execute($signedIn->refreshToken);
        $this->useCase->execute('not-a-refresh-token');
        $this->useCase->execute('');

        $this->expectNotToPerformAssertions();
    }

    private function signIn(): SessionDataOutput
    {
        return $this->createSessionUseCase->execute(new LoginDataInput(
            email: 'alice@lisar.test',
            password: UserFixtures::PLAIN_PASSWORD,
        ), SessionAudienceRegistry::WEBSITE);
    }
}
