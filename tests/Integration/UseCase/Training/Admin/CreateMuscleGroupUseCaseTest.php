<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\Domain\Validation\Constraint\Training\MuscleGroupNameAvailableConstraint;
use App\Domain\Validation\Validator\Training\CreateMuscleGroupValidator;
use App\Fixtures\Training\MuscleFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\CreateMuscleGroupUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Creating a muscle group, under a name no other group has. */
final class CreateMuscleGroupUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MuscleGroupProviderGateway $muscleGroupProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->muscleGroupProviderGateway = self::getContainer()->get(MuscleGroupProviderGateway::class);

        $this->loadFixtures(MuscleFixtures::class);
    }

    public function testItCreatesAGroup(): void
    {
        $output = self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput('Neck'));

        self::assertSame('Neck', $output->name);
        self::assertTrue($output->isActive);
        self::assertNotNull($this->muscleGroupProviderGateway->findOneById($output->id));
    }

    public function testItRefusesAGroupNameAlreadyTakenIgnoringCase(): void
    {
        try {
            self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($this->currentAdmin(), new CreateMuscleGroupDataInput('chest'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateMuscleGroupValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleGroupNameAvailableConstraint::NAME_ALREADY_USED, $exception->violations['name']);
        }
    }

    public function testCreatingAGroupStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(CreateMuscleGroupUseCase::class)->execute($currentUser, new CreateMuscleGroupDataInput('Neck')),
        );
    }
}
