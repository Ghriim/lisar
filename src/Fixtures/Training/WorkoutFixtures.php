<?php

declare(strict_types=1);

namespace App\Fixtures\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Training\WorkoutBlockPersisterGateway;
use App\Domain\Gateway\Persister\Training\WorkoutExercisePersisterGateway;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Persister\Training\WorkoutSetPersisterGateway;
use App\Domain\Tracking\DayClock;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use function sprintf;

/**
 * One finished workout of Alice's, on a past day: a bench press with a warm-up set, then a
 * superset of push-ups and farmer walks. Nothing in progress, so a workout can be started at once.
 */
final class WorkoutFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_FINISHED = 'workout-alice-finished';

    /** How far back the seeded workout sits. */
    public const int DAYS_AGO = 2;

    public function __construct(
        private readonly WorkoutPersisterGateway $workoutPersisterGateway,
        private readonly WorkoutBlockPersisterGateway $workoutBlockPersisterGateway,
        private readonly WorkoutExercisePersisterGateway $workoutExercisePersisterGateway,
        private readonly WorkoutSetPersisterGateway $workoutSetPersisterGateway,
        private readonly DayClock $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $day = $this->clock->today()->modify(sprintf('-%d days', self::DAYS_AGO));

        $workout = new WorkoutDataModel();
        $workout->owner = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $workout->name = 'Push';
        $workout->feeling = 4;
        $workout->startedAt = $this->clock->asStoredInstant($day->setTime(18, 0));
        $workout->finishedAt = $this->clock->asStoredInstant($day->setTime(19, 0));
        $this->workoutPersisterGateway->create($workout);
        $this->addReference(self::ALICE_FINISHED, $workout);

        $warmUp = $this->getReference(SetTypeFixtures::WARM_UP, SetTypeDataModel::class);

        $bench = $this->exercise($this->block($workout, 0), MovementFixtures::BENCH_PRESS_BARBELL, 0);
        $this->set($bench, 0, reps: 10, weight: 40.0, setType: $warmUp);
        $this->set($bench, 1, reps: 8, weight: 60.0, rpe: 7.5);
        $this->set($bench, 2, reps: 8, weight: 60.0, rpe: 8.0);
        $this->set($bench, 3, reps: 7, weight: 60.0, rpe: 9.0);

        $superset = $this->block($workout, 1);
        $pushUp = $this->exercise($superset, MovementFixtures::PUSH_UP, 0);
        $farmerWalk = $this->exercise($superset, MovementFixtures::FARMER_WALK_DUMBBELL, 1);
        $this->set($pushUp, 0, reps: 15);
        $this->set($farmerWalk, 0, weight: 24.0, distance: 40);
        $this->set($pushUp, 1, reps: 12);
        $this->set($farmerWalk, 1, weight: 24.0, distance: 40);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class, MovementFixtures::class, SetTypeFixtures::class];
    }

    private function block(WorkoutDataModel $workout, int $position): WorkoutBlockDataModel
    {
        $block = new WorkoutBlockDataModel();
        $block->workout = $workout;
        $block->position = $position;
        $this->workoutBlockPersisterGateway->create($block);

        return $block;
    }

    private function exercise(WorkoutBlockDataModel $block, string $movementReference, int $position): WorkoutExerciseDataModel
    {
        $exercise = new WorkoutExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $this->getReference($movementReference, MovementDataModel::class);
        $exercise->position = $position;
        $this->workoutExercisePersisterGateway->create($exercise);

        return $exercise;
    }

    private function set(
        WorkoutExerciseDataModel $exercise,
        int $position,
        ?int $reps = null,
        ?float $weight = null,
        ?int $distance = null,
        ?float $rpe = null,
        ?SetTypeDataModel $setType = null,
    ): void {
        $set = new WorkoutSetDataModel();
        $set->exercise = $exercise;
        $set->position = $position;
        $set->reps = $reps;
        $set->weightInKilograms = $weight;
        $set->distanceInMetres = $distance;
        $set->rpe = $rpe;
        $set->setType = $setType ?? $this->getReference(SetTypeFixtures::WORKING, SetTypeDataModel::class);
        // Alice's workout is finished: every set in it was done.
        $set->isComplete = true;
        $this->workoutSetPersisterGateway->create($set);
    }
}
