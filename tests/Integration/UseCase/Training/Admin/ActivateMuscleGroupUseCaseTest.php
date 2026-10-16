<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\ActivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Bringing a retired muscle group back. */
final class ActivateMuscleGroupUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MuscleTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testActivatingAGroupStaysWithinItsQueryBudget(): void
    {
        $id = $this->group(MuscleGroupFixtures::CHEST)->id ?? 0;
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $id);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ActivateMuscleGroupUseCase::class)->execute($currentUser, $id),
        );
    }
}
