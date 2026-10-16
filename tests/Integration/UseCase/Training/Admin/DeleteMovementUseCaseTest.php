<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\StartWorkoutDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Constraint\Training\MovementUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteMovementValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\User\UserFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\QueryCountTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\Admin\DeleteMovementUseCase;
use App\UseCase\Training\StartWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting a common movement, refused once a workout logged it. */
final class DeleteMovementUseCaseTest extends KernelTestCase
{
    use LoadFixturesTrait;
    use MovementTestTrait;
    use QueryCountTrait;
    use SignedInUserTrait;

    private MovementProviderGateway $movementProviderGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movementProviderGateway = self::getContainer()->get(MovementProviderGateway::class);

        $this->loadFixtures(MovementFixtures::class);
    }

    public function testItFailsOnAnUnknownMovement(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        self::getContainer()->get(DeleteMovementUseCase::class)->execute($this->currentAdmin(), 123456789);
    }

    public function testItDeletesAMovement(): void
    {
        $id = $this->movement(MovementFixtures::FARMER_WALK_DUMBBELL)->id ?? 0;

        self::getContainer()->get(DeleteMovementUseCase::class)->execute($this->currentAdmin(), $id);

        self::assertNull($this->movementProviderGateway->findOneCommonById($id));
    }

    /** A movement a workout logged stays in the history: deleting it is refused, anyone's workout. */
    public function testItRefusesToDeleteAMovementAWorkoutLogged(): void
    {
        $this->loadFixtures(MovementFixtures::class, UserFixtures::class);
        $id = $this->movement(MovementFixtures::PUSH_UP)->id ?? 0;
        $aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;

        $workout = self::getContainer()->get(StartWorkoutUseCase::class)->execute($this->currentUser($aliceId), new StartWorkoutDataInput());
        self::getContainer()->get(AddWorkoutBlockUseCase::class)->execute($this->currentUser($aliceId), $workout->id, new AddWorkoutBlockDataInput([new AddWorkoutBlockExerciseDataInput($id)]));

        try {
            self::getContainer()->get(DeleteMovementUseCase::class)->execute($this->currentAdmin(), $id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([MovementUnusedConstraint::IN_USE], $exception->violations['id']);
        }
    }

    public function testDeletingAMovementStaysWithinItsQueryBudget(): void
    {
        $id = $this->movement(MovementFixtures::FARMER_WALK_DUMBBELL)->id ?? 0;

        $this->assertQueryCountAtMost(
            fn () => $this->currentAdmin(),
            fn (UserDataModel $currentUser) => self::getContainer()->get(DeleteMovementUseCase::class)->execute($currentUser, $id),
        );
    }
}
