<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MuscleFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\ActivateMuscleUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Bringing a retired muscle back. */
final class ActivateMuscleUseCaseTest extends KernelTestCase
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

    public function testActivatingAMuscleStaysWithinItsQueryBudget(): void
    {
        $id = $this->muscle(MuscleFixtures::CARDIO)->id ?? 0;
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->currentAdmin(), $id);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(ActivateMuscleUseCase::class)->execute($currentUser, $id),
        );
    }
}
