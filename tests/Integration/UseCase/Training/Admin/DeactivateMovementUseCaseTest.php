<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Retiring a common movement. */
final class DeactivateMovementUseCaseTest extends KernelTestCase
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

    public function testDeactivatingAMovementStaysWithinItsQueryBudget(): void
    {
        $id = $this->movement(MovementFixtures::PUSH_UP)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeactivateMovementUseCase::class)->execute($currentUser, $id),
        );
    }
}
