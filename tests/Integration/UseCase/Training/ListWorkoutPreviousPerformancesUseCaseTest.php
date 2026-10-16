<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\AddWorkoutSetDataInput;
use App\Fixtures\Training\MovementFixtures;
use App\Fixtures\Training\WorkoutFixtures;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Training\ListWorkoutPreviousPerformancesUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** What each movement of a workout gave the last time it was done. */
final class ListWorkoutPreviousPerformancesUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use WorkoutTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadWorkoutFixtures();
    }

    public function testItAnswersWhatEachMovementGaveTheLastTime(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);
        $workout = $this->addBlock($workout, MovementFixtures::FARMER_WALK_DUMBBELL);

        $previous = $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);

        self::assertCount(3, $previous);
        self::assertSame($this->movementId(MovementFixtures::BENCH_PRESS_BARBELL), $previous[0]->movementId);
        self::assertSame([10, 8, 8, 7], array_map(static fn ($set) => $set->reps, $previous[0]->sets));
        self::assertSame([15, 12], array_map(static fn ($set) => $set->reps, $previous[1]->sets));
    }

    /** Done since the seeded « Push », the bench answers its latest workout; the push-ups, the seeded one still. */
    public function testEachMovementAnswersItsLatestTime(): void
    {
        $since = $this->finish($this->addDoneSet($this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL), new AddWorkoutSetDataInput(reps: 3, weightInKilograms: 80.0)));
        // Started an hour before the next one: two starts in the same second are not one before the other.
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $done = $entityManager->find(WorkoutDataModel::class, $since->id);
        self::assertNotNull($done);
        $done->startedAt = $done->startedAt->modify('-1 hour');
        $entityManager->flush();

        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);
        $workout = $this->addBlock($workout, MovementFixtures::PUSH_UP);

        $previous = $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id);

        self::assertCount(2, $previous);
        self::assertSame([3], array_map(static fn ($set) => $set->reps, $previous[0]->sets));
        self::assertSame([15, 12], array_map(static fn ($set) => $set->reps, $previous[1]->sets));
        self::assertNotSame($previous[0]->workoutId, $previous[1]->workoutId);
    }

    public function testTheFirstTimeHasNoLastTime(): void
    {
        $workout = $this->getReference(WorkoutFixtures::ALICE_FINISHED, WorkoutDataModel::class);

        self::assertSame([], $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($this->currentUser($this->idOf($this->alice)), $workout->id ?? 0));
    }

    public function testListingThePreviousPerformancesStaysWithinItsQueryBudget(): void
    {
        $workout = $this->inProgressPush();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($currentUser, $workout->id),
        );
    }

    /** One movement, then three, each done in the seeded « Push ». */
    public function testListingThePreviousPerformancesDoesNotGrowWithTheMovements(): void
    {
        $workout = $this->addBlock($this->start(), MovementFixtures::BENCH_PRESS_BARBELL);

        [$fewer, $more] = $this->assertQueryCountDoesNotGrow(
            fn () => $this->currentUser($this->idOf($this->alice)),
            fn (UserDataModel $currentUser) => $this->useCase(ListWorkoutPreviousPerformancesUseCase::class)->execute($currentUser, $workout->id),
            fn () => $this->addBlock($workout, MovementFixtures::PUSH_UP, MovementFixtures::FARMER_WALK_DUMBBELL),
        );

        self::assertCount(1, $fewer);
        self::assertCount(3, $more);
    }
}
