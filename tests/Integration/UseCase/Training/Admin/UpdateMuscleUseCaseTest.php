<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Constraint\Training\MuscleGroupUsableConstraint;
use App\Domain\Validation\Validator\Training\UpdateMuscleValidator;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use App\UseCase\Training\Admin\UpdateMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Renaming a muscle, or moving it to another group. */
final class UpdateMuscleUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MuscleTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MuscleProviderGateway $muscleProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->muscleProviderGateway = self::getContainer()->get(MuscleProviderGateway::class);

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testItMovesAMuscleToAnotherGroup(): void
    {
        $legs = $this->group(MuscleGroupFixtures::LEGS);

        $output = self::getContainer()->get(UpdateMuscleUseCase::class)
            ->execute($this->currentAdmin(), $this->muscle(MuscleFixtures::CARDIO)->id ?? 0, new UpdateMuscleDataInput('Cardio', $legs->id ?? 0));

        self::assertSame($legs->id, $output->muscleGroupId);
        self::assertSame('Legs', $this->muscleProviderGateway->findOneById($output->id)?->muscleGroup->name);
    }

    public function testItRefusesToMoveAMuscleIntoAnInactiveGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $chest->id ?? 0);

        try {
            self::getContainer()->get(UpdateMuscleUseCase::class)
                ->execute($this->currentAdmin(), $this->muscle(MuscleFixtures::LATS)->id ?? 0, new UpdateMuscleDataInput('Lats', $chest->id ?? 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE, $exception->violations['muscleGroupId']);
        }
    }

    /** Staying in the inactive group it already sits in is not a move. */
    public function testAMuscleInAnInactiveGroupCanStillBeRenamed(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $chest->id ?? 0);

        $output = self::getContainer()->get(UpdateMuscleUseCase::class)
            ->execute($this->currentAdmin(), $this->muscle(MuscleFixtures::UPPER_CHEST)->id ?? 0, new UpdateMuscleDataInput('Clavicular chest', $chest->id ?? 0));

        self::assertSame('Clavicular chest', $output->name);
        self::assertFalse($output->muscleGroupIsActive);
    }

    public function testItFailsOnAnUnknownMuscle(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        self::getContainer()->get(UpdateMuscleUseCase::class)
            ->execute($this->currentAdmin(), 123456789, new UpdateMuscleDataInput('Ghost', $this->group(MuscleGroupFixtures::CHEST)->id ?? 0));
    }

    public function testMovingAMuscleStaysWithinItsQueryBudget(): void
    {
        $id = $this->muscle(MuscleFixtures::CARDIO)->id ?? 0;
        $legsId = $this->group(MuscleGroupFixtures::LEGS)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(UpdateMuscleUseCase::class)->execute($currentUser, $id, new UpdateMuscleDataInput('Cardio', $legsId)),
        );
    }
}
