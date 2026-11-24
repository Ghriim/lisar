<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\HabitPlanningDataOutput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Habits\HabitFixtures;
use App\Fixtures\Planning\HabitPlanningEntryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Planning\ListHabitPlanningUseCase;
use DateTimeImmutable;

/**
 * What the habit planning tests share: Alice reads on Mondays, Wednesdays and Fridays and walks
 * every day, Bob keeps no habit, and the current week — the real one, since a habit is kept today.
 */
trait HabitPlanningTestTrait
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

    private int $aliceId;
    private int $bobId;

    private function loadPlanningFixtures(): void
    {
        $this->loadFixtures(HabitPlanningEntryFixtures::class);

        $this->aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $this->bobId = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class)->id ?? 0;
    }

    private function seededEntryId(): int
    {
        return $this->getReference(HabitPlanningEntryFixtures::ALICE_READING_MONDAY_WEDNESDAY_FRIDAY, HabitPlanningEntryDataModel::class)->id ?? 0;
    }

    private function habitId(string $reference): int
    {
        return $this->getReference($reference, HabitDataModel::class)->id ?? 0;
    }

    private function readingId(): int
    {
        return $this->habitId(HabitFixtures::READING);
    }

    private function walkId(): int
    {
        return $this->habitId(HabitFixtures::WALK);
    }

    /** @param list<int> $weekdays */
    private function weekly(int $habitId, array $weekdays = [1, 2, 3, 4, 5, 6, 7], ?string $startTime = null): SaveHabitPlanningEntryDataInput
    {
        return new SaveHabitPlanningEntryDataInput($habitId, new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::WEEKLY,
            startDate: $this->monday(),
            weekdays: $weekdays,
            startTime: $startTime,
        ));
    }

    private function thisWeek(?int $userId = null): HabitPlanningDataOutput
    {
        $sunday = (new DateTimeImmutable($this->monday()))->modify('+6 days')->format('Y-m-d');

        return $this->useCase(ListHabitPlanningUseCase::class)->execute($this->currentUser($userId ?? $this->aliceId), new ListPlanningDataInput($this->monday(), $sunday));
    }

    private function monday(): string
    {
        $today = self::getContainer()->get(DayClock::class)->today();

        return $today->modify(sprintf('-%d days', (int) $today->format('N') - 1))->format('Y-m-d');
    }

    private function today(): string
    {
        return self::getContainer()->get(DayClock::class)->today()->format('Y-m-d');
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function useCase(string $class): object
    {
        return self::getContainer()->get($class);
    }
}
