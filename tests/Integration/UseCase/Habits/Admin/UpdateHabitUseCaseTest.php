<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\UpdateHabitDataInput;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Fixtures\Habits\HabitFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\UpdateHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Correcting a habit of the catalogue: its name, its icon, and what keeps it. */
final class UpdateHabitUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UpdateHabitUseCase $update;

    protected function setUp(): void
    {
        parent::setUp();

        $this->update = self::getContainer()->get(UpdateHabitUseCase::class);
    }

    /** Turning a tracker habit manual clears the tracker it watched. */
    public function testUpdatingATrackerHabitToManualClearsItsTracker(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $walk = $this->getReference(HabitFixtures::WALK, HabitDataModel::class);

        $output = $this->update->execute(
            $this->currentAdmin(),
            $walk->id ?? 0,
            new UpdateHabitDataInput('Bouger', HabitIconRegistry::RUN, HabitSourceRegistry::MANUAL),
        );

        self::assertSame(HabitSourceRegistry::MANUAL, $output->sourceKind);
        self::assertNull($output->trackerKind);
        self::assertNull($output->trackerThreshold);
    }

    public function testUpdatingAHabitStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $walkId = $this->getReference(HabitFixtures::WALK, HabitDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->update->execute($currentUser, $walkId, new UpdateHabitDataInput('Bouger', HabitIconRegistry::RUN, HabitSourceRegistry::MANUAL)),
        );
    }
}
