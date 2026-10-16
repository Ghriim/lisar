<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\Domain\Validation\Constraint\Training\MuscleGroupUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteMuscleGroupValidator;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMuscleGroupUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleUseCase;
use App\UseCase\Training\Admin\DeleteMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a muscle group, refused while it holds muscles. */
final class DeleteMuscleGroupUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MuscleTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MuscleGroupProviderGateway $muscleGroupProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->muscleGroupProviderGateway = self::getContainer()->get(MuscleGroupProviderGateway::class);

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testItDeletesAnEmptyGroup(): void
    {
        $neck = self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput('Neck'));

        self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute($this->currentAdmin(), $neck->id);

        self::assertNull($this->muscleGroupProviderGateway->findOneById($neck->id));
    }

    /** Inactive muscles count too: every muscle sits in a group, whatever its status. */
    public function testItRefusesToDeleteAGroupHoldingMuscles(): void
    {
        self::getContainer()->get(DeactivateMuscleUseCase::class)->execute($this->currentAdmin(), $this->muscle(MuscleFixtures::CARDIO)->id ?? 0);

        try {
            self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute($this->currentAdmin(), $this->group(MuscleGroupFixtures::OTHER)->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleGroupUnusedConstraint::MUSCLE_GROUP_IN_USE, $exception->violations['id']);
        }
    }

    public function testItFailsOnAnUnknownGroup(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute($this->currentAdmin(), 123456789);
    }

    public function testDeletingAGroupStaysWithinItsQueryBudget(): void
    {
        $id = self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput('Neck'))->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeleteMuscleGroupUseCase::class)->execute($currentUser, $id),
        );
    }
}
