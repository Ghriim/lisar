<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\Input\Training\AddRoutineBlockDataInput;
use App\Domain\DTO\Input\Training\AddRoutineBlockExerciseDataInput;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\DTO\Output\Training\RoutineDataOutput;
use App\Fixtures\Training\MovementFixtures;
use App\UseCase\Training\AddRoutineBlockUseCase;
use App\UseCase\Training\AddRoutineSetUseCase;
use App\UseCase\Training\CreateRoutineUseCase;
use App\UseCase\Training\GetRoutineUseCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * What the routine use cases' tests share, on top of the workouts': the steps that build a routine
 * up — create it, add a block, prescribe a set — and the read that checks what was kept. Alice's
 * seeded « Push » gives her bench press an estimated 1RM of 76 kg (8 × 60 kg).
 */
trait RoutineTestTrait
{
    use WorkoutTestTrait;

    private function createRoutine(string $name = 'Push'): RoutineDataOutput
    {
        return $this->useCase(CreateRoutineUseCase::class)->execute($this->currentUser($this->aliceId), new CreateRoutineDataInput($name));
    }

    private function addRoutineBlock(RoutineDataOutput $routine, string ...$movementReferences): RoutineDataOutput
    {
        $exercises = array_values(array_map(fn (string $reference) => new AddRoutineBlockExerciseDataInput($this->movementId($reference)), $movementReferences));

        return $this->useCase(AddRoutineBlockUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, new AddRoutineBlockDataInput($exercises));
    }

    /** On the first movement of the first block. */
    private function addRoutineSet(RoutineDataOutput $routine, AddRoutineSetDataInput $input): RoutineDataOutput
    {
        return $this->useCase(AddRoutineSetUseCase::class)->execute(
            $this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->exercises[0]->id, $input,
        );
    }

    /** A routine of a bench press block, its one set prescribed at 5 × 80 %. */
    private function benchRoutine(string $name = 'Push'): RoutineDataOutput
    {
        return $this->addRoutineSet($this->addRoutineBlock($this->createRoutine($name), MovementFixtures::BENCH_PRESS_BARBELL), new AddRoutineSetDataInput(5, 80.0));
    }

    /** Read back through a fresh request's worth of loading, not from what the use case held. */
    private function reread(int $routineId): RoutineDataOutput
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return $this->useCase(GetRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routineId);
    }
}
