<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\ActivateMovementFamilyUseCase;
use App\UseCase\Training\Admin\DeactivateMovementFamilyUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Bringing a retired movement family back. */
final class ActivateMovementFamilyUseCaseTest extends KernelTestCase
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

    public function testActivatingAFamilyStaysWithinItsQueryBudget(): void
    {
        $id = $this->family(MovementFamilyFixtures::PLANK)->id ?? 0;
        self::getContainer()->get(DeactivateMovementFamilyUseCase::class)->execute($this->currentAdmin(), $id);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ActivateMovementFamilyUseCase::class)->execute($currentUser, $id),
        );
    }
}
