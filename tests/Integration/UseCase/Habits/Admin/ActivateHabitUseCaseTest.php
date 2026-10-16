<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Habits\HabitFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\ActivateHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Bringing a retired habit back into the offered catalogue. */
final class ActivateHabitUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ActivateHabitUseCase $activate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activate = self::getContainer()->get(ActivateHabitUseCase::class);
    }

    public function testActivatingBringsItBack(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $retired = $this->getReference(HabitFixtures::RETIRED, HabitDataModel::class);

        $output = $this->activate->execute($this->currentAdmin(), $retired->id ?? 0);

        self::assertTrue($output->isActive);
    }

    public function testActivatingAHabitStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $retiredId = $this->getReference(HabitFixtures::RETIRED, HabitDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->activate->execute($currentUser, $retiredId),
        );
    }
}
