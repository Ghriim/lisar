<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateMovementFamilyUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Retiring a movement family. */
final class DeactivateMovementFamilyUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MovementTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testDeactivatingAFamilyStaysWithinItsQueryBudget(): void
    {
        $id = $this->family(MovementFamilyFixtures::PLANK)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeactivateMovementFamilyUseCase::class)->execute($currentUser, $id),
        );
    }
}
