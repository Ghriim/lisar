<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddRoutineSetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Training\RoutineSetPrescriptionConstraint;
use App\Domain\Validation\Validator\Training\AddRoutineSetValidator;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\AddRoutineSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Prescribing a set on a movement of a routine: reps, and a share of the 1RM when it tracks a load. */
final class AddRoutineSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    /** Each set written out on its own: a pyramid is three different sets. */
    public function testItPrescribesSetsAfterTheOnesAlreadyThere(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);
        foreach ([[5, 70.0], [3, 80.0], [1, 90.0]] as [$reps, $percentage]) {
            $routine = $this->addRoutineSet($routine, new AddRoutineSetDataInput($reps, $percentage));
        }

        $sets = $this->reread($routine->id)->blocks[0]->exercises[0]->sets;
        self::assertSame([[5, 70.0], [3, 80.0], [1, 90.0]], array_map(static fn ($set) => [$set->reps, $set->percentageOfOneRepMax], $sets));
        // 90 % of 76 kg is 68.4: rounded down, 68 kg.
        self::assertSame(68.0, $sets[2]->estimatedLoadInKilograms);
    }

    public function testAMovementWithoutALoadTakesRepsAlone(): void
    {
        $routine = $this->addRoutineSet($this->addRoutineBlock($this->createRoutine(), MovementFixtures::PUSH_UP), new AddRoutineSetDataInput(15));

        self::assertSame(15, $routine->blocks[0]->exercises[0]->sets[0]->reps);
        self::assertNull($routine->blocks[0]->exercises[0]->sets[0]->percentageOfOneRepMax);
    }

    public function testItRefusesAShareOnAMovementWithoutALoad(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::PUSH_UP);

        try {
            $this->addRoutineSet($routine, new AddRoutineSetDataInput(15, 70.0));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['percentageOfOneRepMax' => [RoutineSetPrescriptionConstraint::PERCENTAGE_NOT_TRACKED]], $exception->violations);
        }
    }

    public function testItRefusesASetWithoutItsShare(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        try {
            $this->addRoutineSet($routine, new AddRoutineSetDataInput(5));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['percentageOfOneRepMax' => [RoutineSetPrescriptionConstraint::PERCENTAGE_REQUIRED]], $exception->violations);
        }
    }

    public function testItRefusesAShareOffTheHalves(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL);

        try {
            $this->addRoutineSet($routine, new AddRoutineSetDataInput(5, 72.3));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(AddRoutineSetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['percentageOfOneRepMax' => ['percentage_invalid']], $exception->violations);
        }
    }

    public function testAddingASetStaysWithinItsQueryBudget(): void
    {
        $routine = $this->benchRoutine();
        $exerciseId = $routine->blocks[0]->exercises[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(AddRoutineSetUseCase::class)->execute($currentUser, $routine->id, $exerciseId, new AddRoutineSetDataInput(5, 80.0)),
        );
    }
}
