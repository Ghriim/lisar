<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MuscleFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Retiring a muscle. */
final class DeactivateMuscleUseCaseTest extends KernelTestCase
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

    public function testDeactivatingAMuscleStaysWithinItsQueryBudget(): void
    {
        $id = $this->muscle(MuscleFixtures::CARDIO)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($currentUser, $id),
        );
    }
}
