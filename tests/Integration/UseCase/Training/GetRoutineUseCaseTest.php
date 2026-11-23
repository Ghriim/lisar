<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Fixtures\Training\MovementFixtures;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\GetRoutineUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** One of the caller's routines, whole, each set with the load it would give today. */
final class GetRoutineUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** 80 % of the bench's estimated 1RM of 76 kg, rounded down: 60 kg. */
    public function testItReadsARoutineWithTheLoadOfEachSetToday(): void
    {
        $routine = $this->benchRoutine();

        $output = $this->useCase(GetRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id);

        $set = $output->blocks[0]->exercises[0]->sets[0];
        self::assertSame('Bench press (barbell)', $output->blocks[0]->exercises[0]->movement->name);
        self::assertSame([5, 80.0, 60.0], [$set->reps, $set->percentageOfOneRepMax, $set->estimatedLoadInKilograms]);
    }

    /** Never done with a load, never estimated: no load yet. A movement without a load has none to give. */
    public function testAMovementWithoutAnEstimatedOneRepMaxGivesNoLoad(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_DUMBBELL, MovementFixtures::PUSH_UP);
        $routine = $this->addRoutineSet($routine, new AddRoutineSetDataInput(8, 70.0));

        $output = $this->useCase(GetRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id);

        self::assertNull($output->blocks[0]->exercises[0]->sets[0]->estimatedLoadInKilograms);
    }

    public function testSomeoneElsesRoutineIsRefused(): void
    {
        $routine = $this->createRoutine();

        try {
            $this->useCase(GetRoutineUseCase::class)->execute($this->currentUser($this->idOf($this->bob)), $routine->id);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAnUnknownRoutineIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(GetRoutineUseCase::class)->execute($this->currentUser($this->aliceId), 123456789);
    }

    public function testReadingARoutineStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::PUSH_UP)->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(GetRoutineUseCase::class)->execute($currentUser, $routineId),
        );
    }
}
