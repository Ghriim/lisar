<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\Admin\ListEquipmentsForAdminDataInput;
use App\Domain\DTO\Input\Training\CreateEquipmentDataInput;
use App\Fixtures\Training\EquipmentFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateEquipmentUseCase;
use App\UseCase\Training\Admin\DeactivateEquipmentUseCase;
use App\UseCase\Training\Admin\ListEquipmentsForAdminUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Listing the equipments for the back-office, filtered by status, load and distance. */
final class ListEquipmentsForAdminUseCaseTest extends KernelTestCase
{
    use EquipmentTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    /** How many equipments EquipmentFixtures seeds, and how many of them carry a load. */
    private const int SEEDED = 43;
    private const int SEEDED_WITH_WEIGHT = 28;

    private ListEquipmentsForAdminUseCase $list;
    private CreateEquipmentUseCase $create;
    private DeactivateEquipmentUseCase $deactivate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListEquipmentsForAdminUseCase::class);
        $this->create = self::getContainer()->get(CreateEquipmentUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateEquipmentUseCase::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testItListsEveryEquipmentByName(): void
    {
        $names = array_map(static fn ($equipment) => $equipment->name, $this->list->execute($this->currentAdmin()));

        self::assertCount(self::SEEDED, $names);
        self::assertSame('Ab crunch machine', $names[0]);
    }

    public function testTheListFiltersByStatus(): void
    {
        $this->deactivate->execute($this->currentAdmin(), $this->barbell()->id ?? 0);

        self::assertCount(self::SEEDED, $this->list->execute($this->currentAdmin(), new ListEquipmentsForAdminDataInput()));
        self::assertCount(self::SEEDED - 1, $this->list->execute($this->currentAdmin(), new ListEquipmentsForAdminDataInput(isActive: true)));
        self::assertSame(['Barbell'], $this->names(new ListEquipmentsForAdminDataInput(isActive: false)));
    }

    public function testTheListFiltersByLoadAndByDistance(): void
    {
        self::assertCount(self::SEEDED_WITH_WEIGHT, $this->list->execute($this->currentAdmin(), new ListEquipmentsForAdminDataInput(hasWeight: true)));
        self::assertCount(
            self::SEEDED - self::SEEDED_WITH_WEIGHT,
            $this->list->execute($this->currentAdmin(), new ListEquipmentsForAdminDataInput(hasWeight: false)),
        );
        self::assertSame(
            ['Rowing machine', 'Stationary bike', 'Treadmill'],
            $this->names(new ListEquipmentsForAdminDataInput(hasDistance: true)),
        );
    }

    /** The filters combine: every one of them has to hold. */
    public function testTheFiltersCombine(): void
    {
        $this->deactivate->execute($this->currentAdmin(), $this->getReference(EquipmentFixtures::TREADMILL, EquipmentDataModel::class)->id ?? 0);

        self::assertSame(
            ['Rowing machine', 'Stationary bike'],
            $this->names(new ListEquipmentsForAdminDataInput(isActive: true, hasWeight: false, hasDistance: true)),
        );
    }

    public function testListingTheEquipmentsStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser, new ListEquipmentsForAdminDataInput()),
        );
    }

    public function testListingTheEquipmentsDoesNotGrowWithThem(): void
    {
        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->list->execute($currentUser, new ListEquipmentsForAdminDataInput()),
            function (): void {
                $this->create->execute($this->currentAdmin(), new CreateEquipmentDataInput('Climbing rope', false, false));
                $this->create->execute($this->currentAdmin(), new CreateEquipmentDataInput('Sled', true, true));
            },
        );

        self::assertCount(count($fewer) + 2, $more);
    }

    /** @return list<string> */
    private function names(ListEquipmentsForAdminDataInput $input): array
    {
        return array_map(static fn ($equipment) => $equipment->name, $this->list->execute($this->currentAdmin(), $input));
    }
}
