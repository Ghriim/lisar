<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Fixtures\Training\EquipmentFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\ActivateEquipmentUseCase;
use App\UseCase\Training\Admin\DeactivateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Bringing a retired equipment back. */
final class ActivateEquipmentUseCaseTest extends KernelTestCase
{
    use EquipmentTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private DeactivateEquipmentUseCase $deactivate;
    private ActivateEquipmentUseCase $activate;
    private EquipmentProviderGateway $equipmentProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deactivate = self::getContainer()->get(DeactivateEquipmentUseCase::class);
        $this->activate = self::getContainer()->get(ActivateEquipmentUseCase::class);
        $this->equipmentProviderGateway = self::getContainer()->get(EquipmentProviderGateway::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testItDeactivatesAndReactivatesAnEquipment(): void
    {
        $id = $this->barbell()->id ?? 0;

        self::assertFalse($this->deactivate->execute($this->currentAdmin(), $id)->isActive);
        self::assertTrue($this->activate->execute($this->currentAdmin(), $id)->isActive);
        self::assertTrue($this->equipmentProviderGateway->findOneById($id)?->isActive);
    }

    public function testActivatingAnEquipmentStaysWithinItsQueryBudget(): void
    {
        $id = $this->barbell()->id ?? 0;
        $this->deactivate->execute($this->currentAdmin(), $id);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->activate->execute($currentUser, $id),
        );
    }
}
