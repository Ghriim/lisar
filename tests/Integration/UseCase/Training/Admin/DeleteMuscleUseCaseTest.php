<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\Domain\Validation\Constraint\Training\MuscleUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteMuscleValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\MuscleFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\Admin\DeleteMuscleUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a muscle, refused while a movement targets it. */
final class DeleteMuscleUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MovementTestTrait;
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

    public function testItDeletesAMuscle(): void
    {
        $id = $this->muscle(MuscleFixtures::QUADRICEPS)->id ?? 0;

        self::getContainer()->get(DeleteMuscleUseCase::class)->execute($this->currentAdmin(), $id);

        self::assertNull($this->muscleProviderGateway->findOneById($id));
    }

    public function testDeletingAMuscleStaysWithinItsQueryBudget(): void
    {
        $id = $this->muscle(MuscleFixtures::QUADRICEPS)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeleteMuscleUseCase::class)->execute($currentUser, $id),
        );
    }

    /** Held as a secondary muscle only, it is in use all the same. */
    public function testAMuscleAMovementTargetsCannotBeDeleted(): void
    {
        // Reseeded with the movements, so the rule is tried against what they hold on to.
        $this->loadFixtures(MovementFixtures::class);

        try {
            self::getContainer()->get(DeleteMuscleUseCase::class)->execute($this->currentAdmin(), $this->muscleNamed('Front delts')->id ?? 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMuscleValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(MuscleUnusedConstraint::IN_USE, $exception->violations['id']);
        }
    }
}
