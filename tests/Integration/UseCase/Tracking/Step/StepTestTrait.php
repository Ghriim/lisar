<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Step;

use App\Domain\DTO\DataModel\User\UserDataModel;
use LogicException;

/** What the tests of a step count share: the goal a day is given, and signing a seeded account in. */
trait StepTestTrait
{
    private const int DEFAULT_GOAL_IN_STEPS = 5000;

    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }
}
