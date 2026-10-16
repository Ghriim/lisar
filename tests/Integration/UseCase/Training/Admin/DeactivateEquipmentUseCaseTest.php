<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\EquipmentFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Retiring an equipment. */
final class DeactivateEquipmentUseCaseTest extends KernelTestCase
{
    use EquipmentTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private DeactivateEquipmentUseCase $deactivate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deactivate = self::getContainer()->get(DeactivateEquipmentUseCase::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testDeactivatingAnEquipmentStaysWithinItsQueryBudget(): void
    {
        $id = $this->barbell()->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->deactivate->execute($currentUser, $id),
        );
    }
}
