<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Domain\Validation\Validator\Training\AddRoutineExerciseValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\AddRoutineExerciseUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Adding a movement to a block of a routine — which is how a block becomes a superset. */
final class AddRoutineExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItAddsAMovementAtTheEndOfTheBlock(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->useCase(AddRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->id, new AddRoutineExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP), 45));

        self::assertSame(['Bench press (barbell)', 'Push-up'], array_map(static fn ($exercise) => $exercise->movement->name, $output->blocks[0]->exercises));
        self::assertSame(45, $this->reread($routine->id)->blocks[0]->exercises[1]->restInSeconds);
    }

    public function testItRefusesAMovementTrackingADistance(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        try {
            $this->useCase(AddRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->id, new AddRoutineExerciseDataInput($this->movementId(MovementFixtures::FARMER_WALK_DUMBBELL)));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['movementId' => [RoutineMovementsSupportedConstraint::NOT_SUPPORTED]], $exception->violations);
        }
    }

    public function testAddingAMovementStaysWithinItsQueryBudget(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);
        $movementId = $this->movementId(MovementFixtures::PUSH_UP);

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(AddRoutineExerciseUseCase::class)->execute($currentUser, $routine->id, $routine->blocks[0]->id, new AddRoutineExerciseDataInput($movementId)),
        );
    }
}
