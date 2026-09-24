<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Session\SessionAudienceRegistry;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\ActivateUserUseCase;
use App\UseCase\Session\LoginUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ActivateUserUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ActivateUserUseCase $useCase;
    private UserProviderGateway $userProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(ActivateUserUseCase::class);
        $this->userProviderGateway = self::getContainer()->get(UserProviderGateway::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItReactivatesTheAccount(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        self::assertNotNull($bob->id);

        $output = $this->useCase->execute($bob->id);

        self::assertTrue($output->isActive);

        $reread = $this->userProviderGateway->findOneById($bob->id);
        self::assertNotNull($reread);
        self::assertTrue($reread->isActive);
    }

    public function testTheAccountCanSignInAgain(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        self::assertNotNull($bob->id);

        $this->useCase->execute($bob->id);

        $session = self::getContainer()->get(LoginUseCase::class)->execute(new LoginDataInput(
            email: 'bob@lisar.test',
            password: UserFixtures::PLAIN_PASSWORD,
        ), SessionAudienceRegistry::WEBSITE);

        self::assertNotSame('', $session->accessToken);
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase->execute(123456789);
    }
}
