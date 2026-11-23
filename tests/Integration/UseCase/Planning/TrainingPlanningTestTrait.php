<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningDataOutput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Planning\TrainingPlanningEntryFixtures;
use App\Fixtures\Training\RoutineFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Planning\ListTrainingPlanningUseCase;
use DateTimeImmutable;

/**
 * What the training planning tests share: Alice's « Push » planned on Tuesdays and Thursdays, Bob,
 * and the current week — the real one, since starting a workout starts it now.
 */
trait TrainingPlanningTestTrait
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

    private int $aliceId;
    private int $bobId;

    private function loadPlanningFixtures(): void
    {
        $this->loadFixtures(TrainingPlanningEntryFixtures::class);

        $this->aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $this->bobId = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class)->id ?? 0;
    }

    private function seededEntryId(): int
    {
        return $this->getReference(TrainingPlanningEntryFixtures::ALICE_PUSH_TUESDAY_THURSDAY, TrainingPlanningEntryDataModel::class)->id ?? 0;
    }

    private function pushId(): int
    {
        return $this->getReference(RoutineFixtures::ALICE_PUSH, RoutineDataModel::class)->id ?? 0;
    }

    /** Every day of the current week, an entry for each, so that today has a slot. */
    private function everyDay(int $routineId, ?string $startTime = null): SaveTrainingPlanningEntryDataInput
    {
        return new SaveTrainingPlanningEntryDataInput($routineId, new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::WEEKLY,
            startDate: $this->monday(),
            weekdays: [1, 2, 3, 4, 5, 6, 7],
            startTime: $startTime,
        ));
    }

    private function thisWeek(): TrainingPlanningDataOutput
    {
        $sunday = (new DateTimeImmutable($this->monday()))->modify('+6 days')->format('Y-m-d');

        return $this->useCase(ListTrainingPlanningUseCase::class)->execute($this->currentUser($this->aliceId), new ListPlanningDataInput($this->monday(), $sunday));
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
