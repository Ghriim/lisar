<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteRoutineUseCase;
use App\UseCase\Training\GetRoutineUseCase;
use App\UseCase\Training\GetWorkoutUseCase;
use App\UseCase\Training\StartWorkoutFromRoutineUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Deleting one of the caller's routines, with everything it holds. */
final class DeleteRoutineUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItDeletesARoutineWithWhatItHolds(): void
    {
        $routine = $this->benchRoutine();

        $this->useCase(DeleteRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id);

        self::assertNull(self::getContainer()->get(RoutineProviderGateway::class)->findOneById($routine->id));
        $this->expectException(DataModelNotFoundException::class);
        $this->useCase(GetRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id);
    }

    /** A workout it started stays as it is: it loses its link to the routine, nothing else. */
    public function testDeletingARoutineKeepsTheWorkoutsItStarted(): void
    {
        $routine = $this->benchRoutine();
        $workout = $this->useCase(StartWorkoutFromRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id)->workout;

        $this->useCase(DeleteRoutineUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id);

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $kept = $this->useCase(GetWorkoutUseCase::class)->execute($this->currentUser($this->aliceId), $workout->id);
        self::assertNull($kept->routineId);
        self::assertSame($workout->name, $kept->name);
        self::assertSame(60.0, $kept->blocks[0]->exercises[0]->sets[0]->weightInKilograms);
    }

    public function testDeletingARoutineStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->benchRoutine()->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteRoutineUseCase::class)->execute($currentUser, $routineId),
        );
    }
}
