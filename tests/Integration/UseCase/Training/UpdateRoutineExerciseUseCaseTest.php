<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineExerciseDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineExerciseInRoutineConstraint;
use App\Domain\Validation\Validator\Training\UpdateRoutineExerciseValidator;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\UpdateRoutineExerciseUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The note and the rest of a movement of a routine. */
final class UpdateRoutineExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItKeepsANoteAndARest(): void
    {
        $routine = $this->benchRoutine();

        $output = $this->useCase(UpdateRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->exercises[0]->id, new UpdateRoutineExerciseDataInput('Pause en bas', 150));

        self::assertSame(['Pause en bas', 150], [$output->blocks[0]->exercises[0]->note, $output->blocks[0]->exercises[0]->restInSeconds]);
        self::assertSame('Pause en bas', $this->reread($routine->id)->blocks[0]->exercises[0]->note);
    }

    /** Blank means none. */
    public function testABlankNoteClearsIt(): void
    {
        $routine = $this->benchRoutine();
        $exerciseId = $routine->blocks[0]->exercises[0]->id;
        $this->useCase(UpdateRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $exerciseId, new UpdateRoutineExerciseDataInput('Pause en bas'));

        $output = $this->useCase(UpdateRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $exerciseId, new UpdateRoutineExerciseDataInput(''));

        self::assertNull($output->blocks[0]->exercises[0]->note);
    }

    public function testItRefusesAMovementOfAnotherRoutine(): void
    {
        $routine = $this->createRoutine();
        $other = $this->benchRoutine('Pull');

        try {
            $this->useCase(UpdateRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $other->blocks[0]->exercises[0]->id, new UpdateRoutineExerciseDataInput());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateRoutineExerciseValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['exerciseId' => [RoutineExerciseInRoutineConstraint::NOT_FOUND]], $exception->violations);
        }
    }

    public function testUpdatingAMovementStaysWithinItsQueryBudget(): void
    {
        $routine = $this->benchRoutine();
        $exerciseId = $routine->blocks[0]->exercises[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateRoutineExerciseUseCase::class)->execute($currentUser, $routine->id, $exerciseId, new UpdateRoutineExerciseDataInput('Pause en bas', 150)),
        );
    }
}
