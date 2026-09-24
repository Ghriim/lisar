<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\Input\Habit\CreateHabitDataInput;
use App\Domain\DTO\Input\Habit\UpdateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use App\Domain\Registry\Habit\HabitIconRegistry;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use App\Fixtures\HabitFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\ActivateHabitUseCase;
use App\UseCase\Admin\CreateHabitUseCase;
use App\UseCase\Admin\DeactivateHabitUseCase;
use App\UseCase\Admin\ListHabitsForAdminUseCase;
use App\UseCase\Admin\UpdateHabitUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use function count;

/** The back-office maintaining the habit catalogue. */
final class HabitBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    private ListHabitsForAdminUseCase $list;
    private CreateHabitUseCase $create;
    private UpdateHabitUseCase $update;
    private DeactivateHabitUseCase $deactivate;
    private ActivateHabitUseCase $activate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListHabitsForAdminUseCase::class);
        $this->create = self::getContainer()->get(CreateHabitUseCase::class);
        $this->update = self::getContainer()->get(UpdateHabitUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateHabitUseCase::class);
        $this->activate = self::getContainer()->get(ActivateHabitUseCase::class);
    }

    public function testItListsTheWholeCatalogRetiredIncluded(): void
    {
        $this->loadFixtures(HabitFixtures::class);

        // The five seeded habits, retired one included.
        self::assertCount(5, $this->list->execute());
    }

    public function testTheListFiltersByStatus(): void
    {
        $this->loadFixtures(HabitFixtures::class);

        // Four active seeded habits, one retired.
        self::assertCount(5, $this->list->execute(null));
        self::assertCount(4, $this->list->execute(true));
        self::assertCount(1, $this->list->execute(false));
    }

    public function testItCreatesAManualHabit(): void
    {
        $output = $this->create->execute(
            new CreateHabitDataInput('Écrire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL),
        );

        self::assertSame('Écrire', $output->name);
        self::assertSame(HabitSourceRegistry::MANUAL, $output->sourceKind);
        self::assertNull($output->trackerKind);
        self::assertTrue($output->isActive);
    }

    public function testItCreatesATrackerHabit(): void
    {
        $output = $this->create->execute(new CreateHabitDataInput(
            'Marcher',
            HabitIconRegistry::RUN,
            HabitSourceRegistry::TRACKER,
            HabitTrackerRegistry::STEPS,
            8000,
        ));

        self::assertSame(HabitSourceRegistry::TRACKER, $output->sourceKind);
        self::assertSame(HabitTrackerRegistry::STEPS, $output->trackerKind);
        self::assertSame(8000, $output->trackerThreshold);
    }

    public function testItRefusesATrackerHabitWithoutItsMark(): void
    {
        $this->expectException(ValidationException::class);

        $this->create->execute(
            new CreateHabitDataInput('Marcher', HabitIconRegistry::RUN, HabitSourceRegistry::TRACKER),
        );
    }

    /** Turning a tracker habit manual clears the tracker it watched. */
    public function testUpdatingATrackerHabitToManualClearsItsTracker(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $walk = $this->getReference(HabitFixtures::WALK, HabitDataModel::class);

        $output = $this->update->execute(
            $walk->id ?? 0,
            new UpdateHabitDataInput('Bouger', HabitIconRegistry::RUN, HabitSourceRegistry::MANUAL),
        );

        self::assertSame(HabitSourceRegistry::MANUAL, $output->sourceKind);
        self::assertNull($output->trackerKind);
        self::assertNull($output->trackerThreshold);
    }

    public function testDeactivatingRetiresItFromTheOfferedCatalog(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $reading = $this->getReference(HabitFixtures::READING, HabitDataModel::class);

        $before = count(self::getContainer()->get(HabitProviderGateway::class)->findAllActive());

        $output = $this->deactivate->execute($reading->id ?? 0);

        self::assertFalse($output->isActive);
        self::assertSame(
            $before - 1,
            count(self::getContainer()->get(HabitProviderGateway::class)->findAllActive()),
        );
        // The row stays: the admin list still holds it.
        self::assertCount(5, $this->list->execute());
    }

    public function testActivatingBringsItBack(): void
    {
        $this->loadFixtures(HabitFixtures::class);
        $retired = $this->getReference(HabitFixtures::RETIRED, HabitDataModel::class);

        $output = $this->activate->execute($retired->id ?? 0);

        self::assertTrue($output->isActive);
    }

    public function testItFailsOnAnUnknownHabit(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->deactivate->execute(123456789);
    }
}
