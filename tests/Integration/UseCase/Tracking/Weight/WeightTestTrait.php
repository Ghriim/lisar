<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Weight;

use App\Domain\DTO\DataModel\User\UserDataModel;
use LogicException;

/** What the tests of a weight share: the id of a seeded account, to sign it in. */
trait WeightTestTrait
{
    private function idOf(UserDataModel $user): int
    {
        if (null === $user->id) {
            throw new LogicException('The seeded account has no id.');
        }

        return $user->id;
    }
}
