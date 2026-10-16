<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\User\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\User\LoginDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\User\SessionProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\User\SessionAudienceRegistry;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\User\SelfDeactivationConstraint;
use App\Domain\Validation\Validator\User\Admin\DeactivateUserValidator;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\User\Admin\DeactivateUserUseCase;
use App\UseCase\User\LoginUseCase;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DeactivateUserUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

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

    /** On an account with a live session: deactivating it drops that session too. */
    public function testDeactivatingAnAccountStaysWithinItsQueryBudget(): void
    {
        $this->createSessionUseCase->execute(new LoginDataInput(
            email: 'alice@lisar.test',
            password: UserFixtures::PLAIN_PASSWORD,
        ), SessionAudienceRegistry::WEBSITE);
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $adminId = $this->getReference(UserFixtures::ADMIN, UserDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($adminId),
            fn (UserDataModel $currentUser) => $this->useCase->execute($currentUser, $aliceId),
        );
    }

    public function testItDeactivatesTheAccount(): void
    {
        $alice = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($alice->id);
        self::assertNotNull($admin->id);

        $output = $this->useCase->execute($this->currentUser($admin->id), $alice->id);

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

        $this->useCase->execute($this->currentUser($admin->id), $alice->id);

        self::assertSame([], $this->sessionProviderGateway->findAllLiveForUser($alice));
    }

    public function testItRefusesToDeactivateTheAdministratorTheirself(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($admin->id);

        try {
            $this->useCase->execute($this->currentUser($admin->id), $admin->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([SelfDeactivationConstraint::CANNOT_DEACTIVATE_YOURSELF], $exception->violations['id']);
        }

        $reread = $this->userProviderGateway->findOneById($admin->id);
        self::assertNotNull($reread);
        self::assertTrue($reread->isActive);
    }

    /** The back-office checks the role itself, not only the firewall: a user is not allowed. */
    public function testAUserIsNotAllowed(): void
    {
        $alice = $this->userProviderGateway->findOneByEmail('alice@lisar.test') ?? throw new LogicException('Alice was not seeded.');
        $bob = $this->userProviderGateway->findOneByEmail('bob@lisar.test') ?? throw new LogicException('Bob was not seeded.');

        try {
            $this->useCase->execute($alice, $bob->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItIsIdempotentOnAnAlreadyDeactivatedAccount(): void
    {
        $bob = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class);
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($bob->id);
        self::assertNotNull($admin->id);

        self::assertFalse($this->useCase->execute($this->currentUser($admin->id), $bob->id)->isActive);
    }

    public function testItFailsOnAnUnknownAccount(): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, UserDataModel::class);
        self::assertNotNull($admin->id);

        try {
            $this->useCase->execute($this->currentUser($admin->id), 123456789);
            self::fail('Expected DataModelNotFoundException');
        } catch (DataModelNotFoundException $exception) {
            self::assertSame(UserDataModel::class, $exception->dataModelClass);
        }
    }
}
