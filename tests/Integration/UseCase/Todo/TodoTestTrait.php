<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Fixtures\User\UserFixtures;
use LogicException;

/** Reaching the seeded account, TODOs and tasks of the TODO tests by their fixture reference. */
trait TodoTestTrait
{
    private function aliceId(): int
    {
        return $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? throw new LogicException('Alice was not seeded.');
    }

    private function adminId(): int
    {
        return $this->getReference(UserFixtures::ADMIN, UserDataModel::class)->id ?? throw new LogicException('The admin was not seeded.');
    }

    private function todoId(string $reference): int
    {
        return $this->getReference($reference, TodoDataModel::class)->id ?? throw new LogicException('The TODO was not seeded.');
    }

    private function taskId(string $reference): int
    {
        return $this->getReference($reference, TaskDataModel::class)->id ?? throw new LogicException('The task was not seeded.');
    }
}
