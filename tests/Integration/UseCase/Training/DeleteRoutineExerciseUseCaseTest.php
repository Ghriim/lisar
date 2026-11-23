<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteRoutineExerciseUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a movement from a routine. A block never stays empty. */
final class DeleteRoutineExerciseUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItRemovesAMovementFromASuperset(): void
    {
        $routine = $this->addRoutineBlock($this->createRoutine(), MovementFixtures::BENCH_PRESS_BARBELL, MovementFixtures::PUSH_UP);

        $output = $this->useCase(DeleteRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->exercises[0]->id);

        self::assertSame(['Push-up'], array_map(static fn ($exercise) => $exercise->movement->name, $output->blocks[0]->exercises));
        self::assertCount(1, $this->reread($routine->id)->blocks[0]->exercises);
    }

    /** Its last movement takes the block along. */
    public function testTheLastMovementOfABlockTakesTheBlock(): void
    {
        $routine = $this->benchRoutine();

        $output = $this->useCase(DeleteRoutineExerciseUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->exercises[0]->id);

        self::assertSame([], $output->blocks);
        self::assertSame([], $this->reread($routine->id)->blocks);
    }

    public function testRemovingAMovementStaysWithinItsQueryBudget(): void
    {
        $routine = $this->addRoutineBlock($this->benchRoutine(), MovementFixtures::BENCH_PRESS_DUMBBELL, MovementFixtures::PUSH_UP);
        $exerciseId = $routine->blocks[1]->exercises[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteRoutineExerciseUseCase::class)->execute($currentUser, $routine->id, $exerciseId),
        );
    }
}
