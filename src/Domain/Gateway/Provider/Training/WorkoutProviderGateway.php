<?php

declare(strict_types=1);

namespace App\Domain\Gateway\Provider\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutSetDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use DateTimeImmutable;

/**
 * A workout always comes back whole — its blocks, their movements and their families, their sets
 * and set types — because everything that reads one reads all of it.
 */
interface WorkoutProviderGateway
{
    /** Whoever it belongs to: whether the caller may touch it is the validator's to say. */
    public function findOneById(int $id): ?WorkoutDataModel;

    /** Whole, and each movement with the muscles it works: what a workout's stats spread its sets over. */
    public function findOneWithMusclesById(int $id): ?WorkoutDataModel;

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

    /**
     * For each of those movements, the owner's latest finished workout started before `$before`
     * in which it was done, whole, by movement id. A movement never done before is absent.
     *
     * @param list<MovementDataModel> $movements
     *
     * @return array<int, WorkoutDataModel>
     */
    public function findLastFinishedWithMovementsBefore(UserDataModel $owner, array $movements, DateTimeImmutable $before): array;

    /** How many logged exercises, anyone's, are of that movement. */
    public function countExercisesForMovement(MovementDataModel $movement): int;

    /** How many logged sets, anyone's, carry that set type. */
    public function countSetsForSetType(SetTypeDataModel $setType): int;

    /**
     * The owner's sets of those movements that count for personal bests — the done ones — in the
     * order they were done: by workout, then block, movement and set. The
     * movements come mixed: whoever reads them per movement sorts them out, the order holds.
     *
     * @param list<MovementDataModel> $movements
     *
     * @return list<WorkoutSetDataModel>
     */
    public function findSetsCountingForPersonalBests(UserDataModel $owner, array $movements): array;
}
