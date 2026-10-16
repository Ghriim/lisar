<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Fixtures\Training\SetTypeFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\ActivateSetTypeUseCase;
use App\UseCase\Training\Admin\DeactivateSetTypeUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Bringing a retired set type back. */
final class ActivateSetTypeUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SetTypeTestTrait;
    use SignedInUserTrait;

    private DeactivateSetTypeUseCase $deactivate;
    private ActivateSetTypeUseCase $activate;
    private SetTypeProviderGateway $setTypeProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deactivate = self::getContainer()->get(DeactivateSetTypeUseCase::class);
        $this->activate = self::getContainer()->get(ActivateSetTypeUseCase::class);
        $this->setTypeProviderGateway = self::getContainer()->get(SetTypeProviderGateway::class);

        $this->loadFixtures(SetTypeFixtures::class);
    }

    public function testItDeactivatesAndReactivatesASetType(): void
    {
        $id = $this->dropset()->id ?? 0;

        self::assertFalse($this->deactivate->execute($this->currentAdmin(), $id)->isActive);
        self::assertTrue($this->activate->execute($this->currentAdmin(), $id)->isActive);
        self::assertTrue($this->setTypeProviderGateway->findOneById($id)?->isActive);
    }

    public function testActivatingASetTypeStaysWithinItsQueryBudget(): void
    {
        $id = $this->dropset()->id ?? 0;
        $this->deactivate->execute($this->currentAdmin(), $id);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->activate->execute($currentUser, $id),
        );
    }
}
