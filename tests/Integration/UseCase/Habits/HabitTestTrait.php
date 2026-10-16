<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\UseCase\Habits\ListHabitsUseCase;
use LogicException;

/**
 * Finding a seeded habit and reading it back off its keeper's panel: shared by the habit tests,
 * and by the trackers' tests that prove a tracker keeps its own habit.
 */
trait HabitTestTrait
{
    private function habitOf(UserDataModel $owner, string $habitReference): HabitDataOutput
    {
        $wanted = $this->habitId($habitReference);
        foreach (self::getContainer()->get(ListHabitsUseCase::class)->execute($this->currentUser($this->idOf($owner))) as $habit) {
            if ($wanted === $habit->habitId) {
                return $habit;
            }
        }

        throw new LogicException('The habit is not in the list.');
    }

    private function habitId(string $reference): int
    {
        return $this->getReference($reference, HabitDataModel::class)->id ?? 0;
    }

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }
}
