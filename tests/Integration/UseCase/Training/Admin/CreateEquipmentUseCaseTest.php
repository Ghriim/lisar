<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateEquipmentDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Validation\Constraint\Training\EquipmentNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateEquipmentValidator;
use App\Fixtures\Training\EquipmentFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Creating an equipment, under a name no other equipment has. */
final class CreateEquipmentUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private CreateEquipmentUseCase $create;
    private EquipmentProviderGateway $equipmentProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->create = self::getContainer()->get(CreateEquipmentUseCase::class);
        $this->equipmentProviderGateway = self::getContainer()->get(EquipmentProviderGateway::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testItCreatesAnEquipment(): void
    {
        $output = $this->create->execute($this->currentAdmin(), new CreateEquipmentDataInput('Climbing rope', false, false));

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
            $this->create->execute($this->currentAdmin(), new CreateEquipmentDataInput('barbell', true, false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EquipmentNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testCreatingAnEquipmentStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->create->execute($currentUser, new CreateEquipmentDataInput('Climbing rope', false, false)),
        );
    }
}
