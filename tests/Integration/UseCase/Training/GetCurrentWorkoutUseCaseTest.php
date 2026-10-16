<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\GetCurrentWorkoutUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The workout in progress, if there is one. */
final class GetCurrentWorkoutUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testThereIsNoCurrentWorkoutWhenNoneIsInProgress(): void
    {
        self::assertNull($this->useCase(GetCurrentWorkoutUseCase::class)->execute($this->currentUser($this->idOf($this->alice))));
    }

    public function testReadingTheCurrentWorkoutStaysWithinItsQueryBudget(): void
    {
        $this->inProgressPush();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(GetCurrentWorkoutUseCase::class)->execute($currentUser),
        );
    }
}
