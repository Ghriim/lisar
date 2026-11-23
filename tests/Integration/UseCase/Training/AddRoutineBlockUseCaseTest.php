<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineBlockDataInput;
use App\Domain\DTO\Input\Training\AddRoutineBlockExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineBlockMovementsDistinctConstraint;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Training\AddRoutineBlockValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\AddRoutineBlockUseCase;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Adding a block at the end of a routine: one movement, or a superset in the order given. */
final class AddRoutineBlockUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItAddsABlockAndASupersetInTheOrderGiven(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        $output = $this->useCase(AddRoutineBlockUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, new AddRoutineBlockDataInput([
            new AddRoutineBlockExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP), 60),
            new AddRoutineBlockExerciseDataInput($this->movementId(MovementFixtures::BENCH_PRESS_DUMBBELL)),
        ]));

        self::assertCount(2, $output->blocks);
        self::assertSame(['Push-up', 'Bench press (dumbbell)'], array_map(static fn ($exercise) => $exercise->movement->name, $output->blocks[1]->exercises));
        self::assertSame(60, $output->blocks[1]->exercises[0]->restInSeconds);
        self::assertCount(2, $this->reread($routine->id)->blocks);
    }

    /** A plank, a run, a farmer walk wait for the targets that would prescribe them. */
    public function testItRefusesAMovementTrackingADistance(): void
    {
        $routine = $this->createRoutine();

        try {
            $this->addRoutineBlock($routine, MovementFixtures::FARMER_WALK_DUMBBELL);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([RoutineMovementsSupportedConstraint::NOT_SUPPORTED], $exception->violations['exercises']);
        }
    }

    public function testItRefusesAMovementNoLongerOffered(): void
    {
        $routine = $this->createRoutine();
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movementId(MovementFixtures::PUSH_UP));

        try {
            $this->addRoutineBlock($routine, MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['exercises']);
        }
    }

    public function testItRefusesTheSameMovementTwiceInABlock(): void
    {
        $routine = $this->createRoutine();

        try {
            $this->addRoutineBlock($routine, MovementFixtures::PUSH_UP, MovementFixtures::PUSH_UP);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([RoutineBlockMovementsDistinctConstraint::DUPLICATED], $exception->violations['exercises']);
        }
    }

    /** One insert per movement added, beside the block's, and the estimated 1RMs the answer reads. */
    public function testAddingABlockStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->createRoutine()->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(AddRoutineBlockUseCase::class)->execute($currentUser, $routineId, new AddRoutineBlockDataInput([
                new AddRoutineBlockExerciseDataInput($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL)),
                new AddRoutineBlockExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
            ])),
            max: 6,
        );
    }
}
