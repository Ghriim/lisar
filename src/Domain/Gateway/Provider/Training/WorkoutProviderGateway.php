<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\Aggregate\Training\WorkoutTally;
use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutExerciseDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

/**
 * A workout always comes back whole — its blocks, their movements, their sets and set types —
 * because everything that reads one reads all of it.
 */
interface WorkoutProviderGateway
{
    /** Someone else's workout is absent, exactly like one that does not exist. */
    public function findOneByIdForOwner(int $id, UserDataModel $owner): ?WorkoutDataModel;

    public function findOneInProgressForOwner(UserDataModel $owner): ?WorkoutDataModel;

    /**
     * A page of the owner's finished workouts, the latest started first.
     *
     * @return list<WorkoutDataModel>
     */
    public function findFinishedPageForOwner(UserDataModel $owner, int $offset, int $limit): array;

    public function countFinishedForOwner(UserDataModel $owner): int;

    /** How many of the owner's workouts were finished in [`$from`, `$to`). */
    public function countFinishedForOwnerBetween(UserDataModel $owner, DateTimeImmutable $from, DateTimeImmutable $to): int;

    /** The owner's latest finished workout started before `$before` in which that movement was done. */
    public function findLastFinishedWithMovementBefore(UserDataModel $owner, MovementDataModel $movement, DateTimeImmutable $before): ?WorkoutDataModel;

    /** How many logged exercises, anyone's, are of that movement. */
    public function countExercisesForMovement(MovementDataModel $movement): int;

    /** How many logged sets, anyone's, carry that set type. */
    public function countSetsForSetType(SetTypeDataModel $setType): int;

    /**
     * The owner's sets of that movement that count for personal bests — done, of a type that
     * counts — in the order they were done: by workout, then block, movement and set.
     *
     * @return list<WorkoutSetDataModel>
     */
    public function findSetsCountingForPersonalBests(UserDataModel $owner, MovementDataModel $movement): array;

    /**
     * Each of the owner's workouts with what its sets that count add up to, oldest first.
     *
     * @return list<WorkoutTally>
     */
    public function findTalliesForOwner(UserDataModel $owner): array;

    /**
     * The exercises, anyone's, with at least one set of that type — their workout and movement
     * read with them.
     *
     * @return list<WorkoutExerciseDataModel>
     */
    public function findExercisesWithSetType(SetTypeDataModel $setType): array;
}
