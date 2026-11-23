<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateRoutineExerciseDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Validator\Training\CopyRoutineValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\CopyRoutineUseCase;
use App\UseCase\Training\ListRoutinesUseCase;
use App\UseCase\Training\UpdateRoutineExerciseUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Duplicating one of the caller's routines: « Copie de … », holding everything it holds. */
final class CopyRoutineUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItCopiesARoutineWithEverythingItHolds(): void
    {
        $source = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP, MovementFixtures::BENCH_PRESS_DUMBBELL);
        $source = $this->useCase(UpdateRoutineExerciseUseCase::class)->execute(
            $this->currentUser($this->aliceId), $source->id, $source->blocks[0]->exercises[0]->id, new UpdateRoutineExerciseDataInput('Prise serrée', 150),
        );

        $copy = $this->copyRoutine($source->id);

        self::assertNotSame($source->id, $copy->id);
        self::assertSame('Copie de Push', $copy->name);
        $exercise = $copy->blocks[0]->exercises[0];
        self::assertSame(['Bench press (barbell)', 'Prise serrée', 150], [$exercise->movement->name, $exercise->note, $exercise->restInSeconds]);
        // Its sets, each with the load it would give today: 80 % of 76 kg, rounded down.
        self::assertSame([5, 80.0, 60.0], [$exercise->sets[0]->reps, $exercise->sets[0]->percentageOfOneRepMax, $exercise->sets[0]->estimatedLoadInKilograms]);
        self::assertSame(['Push-up', 'Bench press (dumbbell)'], array_map(static fn ($exercise) => $exercise->movement->name, $copy->blocks[1]->exercises));

        // Read back, and the source beside it, unchanged.
        self::assertEquals($copy, $this->reread($copy->id));
        self::assertSame('Push', $this->reread($source->id)->name);
        self::assertSame(['Copie de Push', 'Push'], array_map(
            static fn ($routine) => $routine->name,
            $this->useCase(ListRoutinesUseCase::class)->execute($this->currentUser($this->aliceId)),
        ));
    }

    public function testANameThatWouldRunPastOneHundredCharactersIsCut(): void
    {
        $copy = $this->copyRoutine($this->createRoutine(str_repeat('a', 100))->id);

        self::assertSame('Copie de '.str_repeat('a', 91), $copy->name);
    }

    public function testSomeoneElsesRoutineCannotBeCopied(): void
    {
        $routine = $this->createRoutine();

        try {
            $this->useCase(CopyRoutineUseCase::class)->execute($this->currentUser($this->idOf($this->bob)), $routine->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CopyRoutineValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAnUnknownRoutineIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->copyRoutine(123456789);
    }

    public function testCopyingARoutineStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP)->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(CopyRoutineUseCase::class)->execute($currentUser, $routineId),
            // Two reads — the routine, the estimated 1RMs — then one INSERT per row of the copy:
            // the routine, 2 blocks, 2 movements, 1 set.
            max: 8,
        );
    }

    private function copyRoutine(int $routineId): RoutineDataOutput
    {
        return $this->useCase(CopyRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routineId);
    }
}
