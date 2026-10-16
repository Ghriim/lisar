<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Tracking\Hydration;

use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationDayDataOutput;
use App\UseCase\Tracking\Hydration\CreateHydrationEntryUseCase;
use LogicException;

/**
 * What the tests of a hydration day share: Alice, whose day is read and written, a drink logged
 * on it, and finding a drink again in the day that comes back.
 */
trait HydrationTestTrait
{
    private function aliceId(): int
    {
        return $this->alice->id ?? throw new LogicException('Alice was not seeded.');
    }

    private function drink(int $volumeInMillilitres): HydrationDayDataOutput
    {
        return self::getContainer()->get(CreateHydrationEntryUseCase::class)->execute(
            $this->currentUser($this->aliceId()), new CreateHydrationEntryDataInput($volumeInMillilitres),
        );
    }

    private function entryId(HydrationDayDataOutput $day, int $volumeInMillilitres): int
    {
        foreach ($day->entries as $entry) {
            if ($volumeInMillilitres === $entry->volumeInMillilitres) {
                return $entry->id;
            }
        }

        throw new LogicException('No such drink that day.');
    }
}
