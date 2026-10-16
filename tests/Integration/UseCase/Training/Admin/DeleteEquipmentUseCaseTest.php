<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\EquipmentDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\EquipmentProviderGateway;
use App\Domain\Validation\Constraint\Training\EquipmentUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteEquipmentValidator;
use App\Fixtures\Training\EquipmentFixtures;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeleteEquipmentUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting an equipment, refused while a movement uses it. */
final class DeleteEquipmentUseCaseTest extends KernelTestCase
{
    use EquipmentTestTrait;
    use LoadFixturesTrait;
    use MovementTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private DeleteEquipmentUseCase $delete;
    private EquipmentProviderGateway $equipmentProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->delete = self::getContainer()->get(DeleteEquipmentUseCase::class);
        $this->equipmentProviderGateway = self::getContainer()->get(EquipmentProviderGateway::class);

        $this->loadFixtures(EquipmentFixtures::class);
    }

    public function testItDeletesAnEquipment(): void
    {
        $id = $this->barbell()->id ?? 0;

        $this->delete->execute($this->currentAdmin(), $id);

        self::assertNull($this->equipmentProviderGateway->findOneById($id));
    }

    public function testDeletingAnEquipmentStaysWithinItsQueryBudget(): void
    {
        $id = $this->barbell()->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => $this->delete->execute($currentUser, $id),
        );
    }

    public function testAnEquipmentAMovementUsesCannotBeDeleted(): void
    {
        // Reseeded with the movements, so the rule is tried against what they hold on to.
        $this->loadFixtures(MovementFixtures::class);

        try {
            self::getContainer()->get(DeleteEquipmentUseCase::class)
                ->execute($this->currentAdmin(), $this->getReference(EquipmentFixtures::BENCH, EquipmentDataModel::class)->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteEquipmentValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EquipmentUnusedConstraint::IN_USE, $exception->violations['id']);
        }
    }

    public function testAnEquipmentNoMovementUsesCanBeDeleted(): void
    {
        // Reseeded with the movements, so the rule is tried against what they hold on to.
        $this->loadFixtures(MovementFixtures::class);

        $foamRoller = $this->equipmentNamed('Foam roller');

        self::getContainer()->get(DeleteEquipmentUseCase::class)->execute($this->currentAdmin(), $foamRoller->id ?? 0);

        self::assertNull(self::getContainer()->get(EquipmentProviderGateway::class)->findOneByName('Foam roller'));
    }
}
