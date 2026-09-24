<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Session\LoginDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\SessionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Session\SessionAudienceRegistry;
use App\Domain\Validation\Constraint\User\SelfDeactivationConstraint;
use App\Fixtures\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\DeactivateUserUseCase;
use App\UseCase\Session\LoginUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeactivateUserUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private DeactivateUserUseCase $useCase;
    private LoginUseCase $createSessionUseCase;
    private SessionProviderGateway $sessionProviderGateway;
    private UserProviderGateway $userProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = self::getContainer()->get(DeactivateUserUseCase::class);
        $this->createSessionUseCase = self::getContainer()->get(LoginUseCase::class);
        $this->sessionProviderGateway = self::getContainer()->get(SessionProviderGateway::class);
        $this->userProviderGateway = self::getContainer()->get(UserProviderGateway::class);

        $this->loadFixtures(UserFixtures::class);
    }

    public function testItDeactivatesTheAccount(): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($alice->id);
        self::assertNotNull($admin->id);

        $output = $this->useCase->execute($alice->id, $admin->id);

        self::assertFalse($output->isActive);

        // Re-read through the gateway: assert it was really persisted.
        $reread = $this->userProviderGateway->findOneById($alice->id);
        self::assertNotNull($reread);
        self::assertFalse($reread->isActive);
    }

    public function testItDropsTheLiveSessionsOfTheAccount(): void
    {
        $this->createSessionUseCase->execute(new LoginDataInput(
            email: 'alice@lisar.test',
            password: UserFixtures::PLAIN_PASSWORD,
        ), SessionAudienceRegistry::WEBSITE);

        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($alice->id);
        self::assertNotNull($admin->id);
        self::assertCount(1, $this->sessionProviderGateway->findAllLiveForUser($alice));

        $this->useCase->execute($alice->id, $admin->id);

        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($alice));
    }

    public function testItRefusesToDeactivateTheAdministratorTheirself(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($admin->id);

        try {
            $this->useCase->execute($admin->id, $admin->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateUserUseCase::ERROR_CODE, $exception->errorCode);
            self::assertSame([SelfDeactivationConstraint::CANNOT_DEACTIVATE_YOURSELF], $exception->violations['id']);
        }

        $reread = $this->userProviderGateway->findOneById($admin->id);
        self::assertNotNull($reread);
        self::assertTrue($reread->isActive);
    }

    public function testItIsIdempotentOnAnAlreadyDeactivatedAccount(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($bob->id);
        self::assertNotNull($admin->id);

        self::assertFalse($this->useCase->execute($bob->id, $admin->id)->isActive);
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($admin->id);

        try {
            $this->useCase->execute(123456789, $admin->id);
            self::fail('Expected DataModelNotFoundException');
        } catch (DataModelNotFoundException $exception) {
            self::assertSame(UserDataModel::class, $exception->dataModelClass);
        }
    }
}
