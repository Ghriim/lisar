<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMovementFamilyDataInput;
use App\Fixtures\Training\MovementFamilyFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\UpdateMovementFamilyUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Renaming a movement family. */
final class UpdateMovementFamilyUseCaseTest extends KernelTestCase
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

    public function testRenamingAFamilyStaysWithinItsQueryBudget(): void
    {
        $id = $this->family(MovementFamilyFixtures::PLANK)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(UpdateMovementFamilyUseCase::class)->execute($currentUser, $id, new UpdateMovementFamilyDataInput('Front plank')),
        );
    }
}
