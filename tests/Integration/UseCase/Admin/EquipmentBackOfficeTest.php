<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Admin;

use App\Domain\DTO\DataModel\EquipmentDataModel;
use App\Domain\DTO\Input\Admin\ListEquipmentsForAdminDataInput;
use App\Domain\DTO\Input\Workout\CreateEquipmentDataInput;
use App\Domain\DTO\Input\Workout\UpdateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\EquipmentProviderGateway;
use App\Domain\Validation\Constraint\Workout\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\Workout\CreateEquipmentValidator;
use App\Domain\Validation\Validator\Workout\UpdateEquipmentValidator;
use App\Fixtures\EquipmentFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\UseCase\Admin\ActivateEquipmentUseCase;
use App\UseCase\Admin\CreateEquipmentUseCase;
use App\UseCase\Admin\DeactivateEquipmentUseCase;
use App\UseCase\Admin\DeleteEquipmentUseCase;
use App\UseCase\Admin\ListEquipmentsForAdminUseCase;
use App\UseCase\Admin\UpdateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The back-office maintaining the equipments movements are done with. */
final class EquipmentBackOfficeTest extends KernelTestCase
{
    use LoadFixturesTrait;

    /** How many equipments EquipmentFixtures seeds, and how many of them carry a load. */
    private const int SEEDED = 43;
    private const int SEEDED_WITH_WEIGHT = 28;

    private ListEquipmentsForAdminUseCase $list;
    private CreateEquipmentUseCase $create;
    private UpdateEquipmentUseCase $update;
    private DeactivateEquipmentUseCase $deactivate;
    private ActivateEquipmentUseCase $activate;
    private DeleteEquipmentUseCase $delete;
    private EquipmentProviderGateway $equipmentProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = self::getContainer()->get(ListEquipmentsForAdminUseCase::class);
        $this->create = self::getContainer()->get(CreateEquipmentUseCase::class);
        $this->update = self::getContainer()->get(UpdateEquipmentUseCase::class);
        $this->deactivate = self::getContainer()->get(DeactivateEquipmentUseCase::class);
        $this->activate = self::getContainer()->get(ActivateEquipmentUseCase::class);
        $this->delete = self::getContainer()->get(DeleteEquipmentUseCase::class);
        $this->equipmentProviderGateway = self::getContainer()->get(EquipmentProviderGateway::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testItListsEveryEquipmentByName(): void
    {
        $names = array_map(static fn ($equipment) => $equipment->name, $this->list->execute());

        self::assertCount(self::SEEDED, $names);
        self::assertSame('Ab crunch machine', $names[0]);
    }

    public function testTheListFiltersByStatus(): void
    {
        $this->deactivate->execute($this->barbell()->id ?? 0);

        self::assertCount(self::SEEDED, $this->list->execute(new ListEquipmentsForAdminDataInput()));
        self::assertCount(self::SEEDED - 1, $this->list->execute(new ListEquipmentsForAdminDataInput(isActive: true)));
        self::assertSame(['Barbell'], $this->names(new ListEquipmentsForAdminDataInput(isActive: false)));
    }

    public function testTheListFiltersByLoadAndByDistance(): void
    {
        self::assertCount(self::SEEDED_WITH_WEIGHT, $this->list->execute(new ListEquipmentsForAdminDataInput(hasWeight: true)));
        self::assertCount(
            self::SEEDED - self::SEEDED_WITH_WEIGHT,
            $this->list->execute(new ListEquipmentsForAdminDataInput(hasWeight: false)),
        );
        self::assertSame(
            ['Rowing machine', 'Stationary bike', 'Treadmill'],
            $this->names(new ListEquipmentsForAdminDataInput(hasDistance: true)),
        );
    }

    /** The filters combine: every one of them has to hold. */
    public function testTheFiltersCombine(): void
    {
        $this->deactivate->execute($this->getReference(EquipmentFixtures::TREADMILL, EquipmentDataModel::class)->id ?? 0);

        self::assertSame(
            ['Rowing machine', 'Stationary bike'],
            $this->names(new ListEquipmentsForAdminDataInput(isActive: true, hasWeight: false, hasDistance: true)),
        );
    }

    public function testItCreatesAnEquipment(): void
    {
        $output = $this->create->execute(new CreateEquipmentDataInput('Climbing rope', false, false));

        self::assertSame('Climbing rope', $output->name);
        self::assertFalse($output->hasWeight);
        self::assertFalse($output->hasDistance);
        self::assertTrue($output->isActive);

        $equipment = $this->equipmentProviderGateway->findOneById($output->id);
        self::assertNotNull($equipment);
        self::assertSame('Climbing rope', $equipment->name);
    }

    /** "Barbell" and "barbell" are one name: the collation compares ignoring case. */
    public function testItRefusesANameAlreadyTakenIgnoringCase(): void
    {
        try {
            $this->create->execute(new CreateEquipmentDataInput('barbell', true, false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EquipmentNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItUpdatesAnEquipment(): void
    {
        $treadmill = $this->getReference(EquipmentFixtures::TREADMILL, EquipmentDataModel::class);

        $output = $this->update->execute($treadmill->id ?? 0, new UpdateEquipmentDataInput('Weighted treadmill', true, true));

        self::assertSame('Weighted treadmill', $output->name);
        self::assertTrue($output->hasWeight);
        self::assertTrue($output->hasDistance);

        $reread = $this->equipmentProviderGateway->findOneById($output->id);
        self::assertNotNull($reread);
        self::assertTrue($reread->hasWeight);
    }

    /** An equipment keeps its own name, and may change only its case. */
    public function testAnEquipmentMayKeepItsOwnName(): void
    {
        $output = $this->update->execute($this->barbell()->id ?? 0, new UpdateEquipmentDataInput('BARBELL', true, false));

        self::assertSame('BARBELL', $output->name);
    }

    public function testItRefusesToRenameOntoAnotherEquipment(): void
    {
        try {
            $this->update->execute($this->barbell()->id ?? 0, new UpdateEquipmentDataInput('Dumbbell', true, false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EquipmentNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItDeactivatesAndReactivatesAnEquipment(): void
    {
        $id = $this->barbell()->id ?? 0;

        self::assertFalse($this->deactivate->execute($id)->isActive);
        self::assertTrue($this->activate->execute($id)->isActive);
        self::assertTrue($this->equipmentProviderGateway->findOneById($id)?->isActive);
    }

    public function testItDeletesAnEquipment(): void
    {
        $id = $this->barbell()->id ?? 0;

        $this->delete->execute($id);

        self::assertNull($this->equipmentProviderGateway->findOneById($id));
    }

    public function testItFailsOnAnUnknownEquipment(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->update->execute(123456789, new UpdateEquipmentDataInput('Ghost', false, false));
    }

    /** @return list<string> */
    private function names(ListEquipmentsForAdminDataInput $input): array
    {
        return array_map(static fn ($equipment) => $equipment->name, $this->list->execute($input));
    }

    private function barbell(): EquipmentDataModel
    {
        return $this->getReference(EquipmentFixtures::BARBELL, EquipmentDataModel::class);
    }
}
