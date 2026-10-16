<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Habits\CreateHabitDataInput;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Fixtures\Habits\HabitFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Habits\Admin\CreateHabitUseCase;
use App\UseCase\Habits\Admin\ListHabitsForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function count;

/** The back-office's view of the habit catalogue: every habit, retired ones included. */
final class ListHabitsForAdminUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private ListHabitsForAdminUseCase $list;
    private CreateHabitUseCase $create;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHabitsForAdminUseCase::class);
        $this->create = self::getContainer()->get(CreateHabitUseCase::class);
    }

    public function testItListsTheWholeCatalogRetiredIncluded(): void
    {
        $this->loadFixtures(HabitFixtures::class);

        // The five seeded habits, retired one included.
        self::assertCount(5, $this->list->execute($this->currentAdmin()));
    }

    public function testTheListFiltersByStatus(): void
    {
        $this->loadFixtures(HabitFixtures::class);

        // Four active seeded habits, one retired.
        self::assertCount(5, $this->list->execute($this->currentAdmin(), null));
        self::assertCount(4, $this->list->execute($this->currentAdmin(), true));
        self::assertCount(1, $this->list->execute($this->currentAdmin(), false));
    }

    public function testListingTheCatalogStaysWithinItsQueryBudget(): void
    {
        $this->loadFixtures(HabitFixtures::class);

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
        );
    }

    public function testListingTheCatalogDoesNotGrowWithIt(): void
    {
        $this->loadFixtures(HabitFixtures::class);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser),
            function (): void {
                $this->create->execute($this->currentAdmin(), new CreateHabitDataInput('Écrire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL));
                $this->create->execute($this->currentAdmin(), new CreateHabitDataInput('Marcher', HabitIconRegistry::RUN, HabitSourceRegistry::TRACKER, HabitTrackerRegistry::STEPS, 8000));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }
}
