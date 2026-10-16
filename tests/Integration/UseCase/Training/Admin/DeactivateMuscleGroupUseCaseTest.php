<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\ActivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Retiring a muscle group, and with it the muscles it holds. */
final class DeactivateMuscleGroupUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MuscleTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MuscleProviderGateway $muscleProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->muscleProviderGateway = self::getContainer()->get(MuscleProviderGateway::class);

        $this->loadFixtures(MuscleFixtures::class);
    }

    /** Deactivating a group withdraws its muscles without touching their own flag. */
    public function testDeactivatingAGroupLeavesItsMusclesOwnFlagAlone(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);

        self::assertFalse(self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $chest->id ?? 0)->isActive);

        $upperChest = $this->muscleProviderGateway->findOneById($this->muscle(MuscleFixtures::UPPER_CHEST)->id ?? 0);
        self::assertNotNull($upperChest);
        self::assertTrue($upperChest->isActive);
        self::assertFalse($upperChest->muscleGroup->isActive);

        self::assertTrue(self::getContainer()->get(ActivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $chest->id ?? 0)->isActive);
    }

    public function testDeactivatingAGroupStaysWithinItsQueryBudget(): void
    {
        $id = $this->group(MuscleGroupFixtures::CHEST)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($currentUser, $id),
        );
    }
}
