<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutBlockDataInput;
use App\Domain\DTO\Input\Training\AddWorkoutBlockExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\WorkoutMovementsOfferedConstraint;
use App\Domain\Validation\Validator\Training\AddWorkoutBlockValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Infrastructure\HttpKernel\ArgumentResolver\DataInputValueResolver;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\AddWorkoutBlockUseCase;
use App\UseCase\Training\Admin\DeactivateMovementUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/** Adding a block to a workout: one movement, or several done one after the other as a superset. */
final class AddWorkoutBlockUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItAddsABlockAndASuperset(): void
    {
        $workout = $this->start();

        $this->addBlock($workout, MovementFixtures::BENCH_PRESS_BARBELL);
        $output = $this->addBlock($workout, MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);

        self::assertCount(2, $output->blocks);
        self::assertSame(['Bench press (barbell)'], $this->movementNames($output, 0));
        // The superset keeps the order it was given in.
        self::assertSame(['Push-up', 'Farmer walk (dumbbell)'], $this->movementNames($output, 1));
    }

    /** Given against the order the movements were created in: the block keeps the one given. */
    public function testASupersetKeepsTheOrderGivenWhateverTheIds(): void
    {
        $output = $this->addBlock($this->start(), MovementFixtures::FARMER_WALK_DUMBBELL, MovementFixtures::PUSH_UP);

        self::assertSame(['Farmer walk (dumbbell)', 'Push-up'], $this->movementNames($output, 0));
    }

    public function testEachMovementOfASupersetKeepsItsOwnRest(): void
    {
        $output = $this->useCase(AddWorkoutBlockUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $this->start()->id, new AddWorkoutBlockDataInput([
            new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
            new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::FARMER_WALK_DUMBBELL), 90),
        ]));

        self::assertNull($output->blocks[0]->exercises[0]->restInSeconds);
        self::assertSame(90, $output->blocks[0]->exercises[1]->restInSeconds);
    }

    /** The API sends a block as a list of objects: the real serializer has to build each one. */
    public function testABlockIsReadFromTheJsonTheApiSends(): void
    {
        $request = new Request(content: (string) json_encode(['exercises' => [
            ['movementId' => 4, 'restInSeconds' => 90],
            ['movementId' => 7],
        ]]));
        $argument = new ArgumentMetadata('input', AddWorkoutBlockDataInput::class, false, false, null);

        $input = self::getContainer()->get(DataInputValueResolver::class)->resolve($request, $argument)[0];

        self::assertInstanceOf(AddWorkoutBlockDataInput::class, $input);
        self::assertEquals([new AddWorkoutBlockExerciseDataInput(4, 90), new AddWorkoutBlockExerciseDataInput(7)], $input->exercises);
    }

    public function testTheSameMovementMayComeTwice(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP);

        $output = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        self::assertCount(2, $output->blocks);
    }

    public function testItRefusesAMovementNoLongerOffered(): void
    {
        $workout = $this->start();
        $this->useCase(DeactivateMovementUseCase::class)->execute($this->currentAdmin(), $this->movementId(MovementFixtures::PUSH_UP));

        try {
            $this->addBlock($workout, MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddWorkoutBlockValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame([WorkoutMovementsOfferedConstraint::UNAVAILABLE], $exception->violations['exercises']);
        }
    }

    public function testAddingABlockStaysWithinItsQueryBudget(): void
    {
        $workoutId = $this->start()->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(AddWorkoutBlockUseCase::class)->execute($currentUser, $workoutId, new AddWorkoutBlockDataInput([
                new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::PUSH_UP)),
                new AddWorkoutBlockExerciseDataInput($this->movementId(MovementFixtures::FARMER_WALK_DUMBBELL)),
            ])),
        );
    }
}
