<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Validation\Constraint\Training\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\UpdateEquipmentValidator;
use App\Fixtures\Training\EquipmentFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\UpdateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Changing an equipment: its name, and whether it carries a load or covers a distance. */
final class UpdateEquipmentUseCaseTest extends KernelTestCase
{
    use EquipmentTestTrait;
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private UpdateEquipmentUseCase $update;
    private EquipmentProviderGateway $equipmentProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->update = self::getContainer()->get(UpdateEquipmentUseCase::class);
        $this->equipmentProviderGateway = self::getContainer()->get(EquipmentProviderGateway::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testItUpdatesAnEquipment(): void
    {
        $treadmill = $this->getReference(EquipmentFixtures::TREADMILL, EquipmentDataModel::class);

        $output = $this->update->execute($this->currentAdmin(), $treadmill->id ?? 0, new UpdateEquipmentDataInput('Weighted treadmill', true, true));

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
        $output = $this->update->execute($this->currentAdmin(), $this->barbell()->id ?? 0, new UpdateEquipmentDataInput('BARBELL', true, false));

        self::assertSame('BARBELL', $output->name);
    }

    public function testItRefusesToRenameOntoAnotherEquipment(): void
    {
        try {
            $this->update->execute($this->currentAdmin(), $this->barbell()->id ?? 0, new UpdateEquipmentDataInput('Dumbbell', true, false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EquipmentNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItFailsOnAnUnknownEquipment(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->update->execute($this->currentAdmin(), 123456789, new UpdateEquipmentDataInput('Ghost', false, false));
    }

    public function testUpdatingAnEquipmentStaysWithinItsQueryBudget(): void
    {
        $id = $this->getReference(EquipmentFixtures::TREADMILL, EquipmentDataModel::class)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->update->execute($currentUser, $id, new UpdateEquipmentDataInput('Weighted treadmill', true, true)),
        );
    }
}
