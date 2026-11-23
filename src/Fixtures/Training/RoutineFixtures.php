<?php

declare(strict_types=1);

namespace App\Fixtures\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\RoutineBlockDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\Training\RoutineExerciseDataModel;
use App\Domain\DTO\DataModel\Training\RoutineSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Gateway\Persister\Training\RoutinePersisterGateway;
use App\Fixtures\User\UserFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Alice's « Push » routine: a bench press she has an estimated 1RM for, from her seeded workout,
 * and push-ups, which carry reps alone. Built whole and persisted in one go, as a copy would be.
 */
final class RoutineFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_PUSH = 'routine-alice-push';

    public function __construct(
        private readonly RoutinePersisterGateway $routinePersisterGateway,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $routine = new RoutineDataModel();
        $routine->owner = $this->getReference(UserFixtures::ALICE, UserDataModel::class);
        $routine->name = 'Push';

        $bench = $this->exercise($this->block($routine, 0), MovementFixtures::BENCH_PRESS_BARBELL, rest: 150);
        foreach ([[5, 75.0], [5, 80.0], [5, 80.0]] as $position => [$reps, $percentage]) {
            $this->set($bench, $position, $reps, $percentage);
        }

        $pushUp = $this->exercise($this->block($routine, 1), MovementFixtures::PUSH_UP, rest: 60);
        foreach ([15, 12, 10] as $position => $reps) {
            $this->set($pushUp, $position, $reps, null);
        }

        $this->routinePersisterGateway->create($routine);
        $this->addReference(self::ALICE_PUSH, $routine);
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [UserFixtures::class, MovementFixtures::class, WorkoutFixtures::class];
    }

    private function block(RoutineDataModel $routine, int $position): RoutineBlockDataModel
    {
        $block = new RoutineBlockDataModel();
        $block->routine = $routine;
        $block->position = $position;
        $routine->blocks->add($block);

        return $block;
    }

    private function exercise(RoutineBlockDataModel $block, string $movementReference, int $rest): RoutineExerciseDataModel
    {
        $exercise = new RoutineExerciseDataModel();
        $exercise->block = $block;
        $exercise->movement = $this->getReference($movementReference, MovementDataModel::class);
        $exercise->restInSeconds = $rest;
        $block->exercises->add($exercise);

        return $exercise;
    }

    private function set(RoutineExerciseDataModel $exercise, int $position, int $reps, ?float $percentage): void
    {
        $set = new RoutineSetDataModel();
        $set->exercise = $exercise;
        $set->position = $position;
        $set->reps = $reps;
        $set->percentageOfOneRepMax = $percentage;
        $exercise->sets->add($set);
    }
}
