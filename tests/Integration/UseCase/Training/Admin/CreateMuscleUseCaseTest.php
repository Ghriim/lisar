<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Constraint\Training\MuscleGroupUsableConstraint;
use App\Domain\Validation\Constraint\Training\MuscleNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMuscleValidator;
use App\Fixtures\Training\MuscleFixtures;
use App\Fixtures\Training\MuscleGroupFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMuscleUseCase;
use App\UseCase\Training\Admin\DeactivateMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Creating a muscle in an active group, under a name no other muscle has. */
final class CreateMuscleUseCaseTest extends KernelTestCase
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

    public function testItCreatesAMuscleInAGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);

        $output = self::getContainer()->get(CreateMuscleUseCase::class)->execute($this->currentAdmin(), new CreateMuscleDataInput('Serratus', $chest->id ?? 0));

        self::assertSame('Serratus', $output->name);
        self::assertSame($chest->id, $output->muscleGroupId);
        self::assertSame('Chest', $output->muscleGroupName);
        self::assertTrue($output->muscleGroupIsActive);
        self::assertSame('Chest', $this->muscleProviderGateway->findOneById($output->id)?->muscleGroup->name);
    }

    /** Unique across every group, not only within its own. */
    public function testItRefusesAMuscleNameTakenInAnotherGroup(): void
    {
        try {
            self::getContainer()->get(CreateMuscleUseCase::class)
                ->execute($this->currentAdmin(), new CreateMuscleDataInput('lats', $this->group(MuscleGroupFixtures::SHOULDERS)->id ?? 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testItRefusesAnUnknownGroup(): void
    {
        try {
            self::getContainer()->get(CreateMuscleUseCase::class)->execute($this->currentAdmin(), new CreateMuscleDataInput('Serratus', 123456789));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_NOT_FOUND, $exception->violations['muscleGroupId']);
        }
    }

    public function testItRefusesToCreateAMuscleInAnInactiveGroup(): void
    {
        $chest = $this->group(MuscleGroupFixtures::CHEST);
        self::getContainer()->get(DeactivateMuscleGroupUseCase::class)->execute($this->currentAdmin(), $chest->id ?? 0);

        try {
            self::getContainer()->get(CreateMuscleUseCase::class)->execute($this->currentAdmin(), new CreateMuscleDataInput('Serratus', $chest->id ?? 0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(MuscleGroupUsableConstraint::MUSCLE_GROUP_INACTIVE, $exception->violations['muscleGroupId']);
        }
    }

    public function testCreatingAMuscleStaysWithinItsQueryBudget(): void
    {
        $chestId = $this->group(MuscleGroupFixtures::CHEST)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(CreateMuscleUseCase::class)->execute($currentUser, new CreateMuscleDataInput('Serratus', $chestId)),
        );
    }
}
