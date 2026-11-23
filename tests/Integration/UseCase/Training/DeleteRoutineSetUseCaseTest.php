<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\DeleteRoutineSetUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Removing a prescribed set. The movement stays, without it. */
final class DeleteRoutineSetUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use RoutineTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItRemovesASetAndKeepsTheMovement(): void
    {
        $routine = $this->benchRoutine();

        $output = $this->useCase(DeleteRoutineSetUseCase::class)->execute($this->currentUser($this->aliceId), $routine->id, $routine->blocks[0]->exercises[0]->sets[0]->id);

        self::assertSame([], $output->blocks[0]->exercises[0]->sets);
        self::assertSame([], $this->reread($routine->id)->blocks[0]->exercises[0]->sets);
    }

    public function testRemovingASetStaysWithinItsQueryBudget(): void
    {
        $routine = $this->benchRoutine();
        $setId = $routine->blocks[0]->exercises[0]->sets[0]->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(DeleteRoutineSetUseCase::class)->execute($currentUser, $routine->id, $setId),
        );
    }
}
