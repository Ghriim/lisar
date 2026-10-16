<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\Training\MovementFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteWorkoutBlockUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a whole block from a workout, its movements and their sets with it. */
final class DeleteWorkoutBlockUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItRemovesAWholeBlock(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL);

        $output = $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id, $workout->blocks[0]->id);

        self::assertSame([], $output->blocks);
    }

    public function testRemovingABlockStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();
        $blockId = $workout->blocks[1]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteWorkoutBlockUseCase::class)->execute($currentUser, $workout->id, $blockId),
        );
    }
}
