<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Session;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Session\CreateSessionDataInput;
use App\Domain\DTO\Output\Session\SessionDataOutput;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Session\RefreshTokenGenerator;
use App\Fixtures\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Session\CreateSessionUseCase;
use App\UseCase\Session\DeleteSessionUseCase;
use App\UseCase\Session\RefreshSessionUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeleteSessionUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private DeleteSessionUseCase $useCase;
    private CreateSessionUseCase $createSessionUseCase;
    private SessionProviderGateway $sessionProviderGateway;
    private RefreshTokenGenerator $refreshTokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(DeleteSessionUseCase::class);
        $this->createSessionUseCase = self::getContainer()->get(CreateSessionUseCase::class);
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
        $refreshed = self::getContainer()->get(RefreshSessionUseCase::class)->execute($signedIn->refreshToken);

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
        return $this->createSessionUseCase->execute(new CreateSessionDataInput(
            email: 'alice@lisar.test',
            password: UserFixtures::PLAIN_PASSWORD,
        ));
    }
}
