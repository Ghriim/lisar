<?php

declare(strict_types=1);

namespace App\Tests;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Registry\User\UserRoleRegistry;

/**
 * The caller a unit test hands a validator or a constraint, built by hand, and data models that
 * are theirs. An integration test loads its caller instead: see Integration\SignedInUserTrait.
 */
trait CurrentUserTrait
{
    private function currentUser(int $id, string $role = UserRoleRegistry::USER, bool $isActive = true): UserDataModel
    {
        $user = new UserDataModel();
        $user->id = $id;
        $user->role = $role;
        $user->isActive = $isActive;

        return $user;
    }

    private function currentAdmin(): UserDataModel
    {
        return $this->currentUser(1000, UserRoleRegistry::ADMIN);
    }

    /** A signed-in account, active, with no role but the user's. */
    private function anyCurrentUser(): UserDataModel
    {
        return $this->currentUser(1);
    }

    private function workoutOf(UserDataModel $owner): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        $workout->owner = $owner;

        return $workout;
    }

    private function taskOf(UserDataModel $owner): TaskDataModel
    {
        $task = new TaskDataModel();
        $task->owner = $owner;

        return $task;
    }

    private function hydrationEntryOf(UserDataModel $owner): HydrationEntryDataModel
    {
        $day = new HydrationDayDataModel();
        $day->owner = $owner;
        $entry = new HydrationEntryDataModel();
        $entry->hydrationDay = $day;

        return $entry;
    }
}
