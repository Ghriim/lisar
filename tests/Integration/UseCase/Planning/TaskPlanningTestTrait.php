<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\TaskPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningDataOutput;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Tracking\DayClock;
use App\Fixtures\Planning\TaskPlanningEntryFixtures;
use App\Fixtures\Todo\CategoryFixtures;
use App\Fixtures\User\UserFixtures;
use App\Tests\Integration\LoadFixturesTrait;
use App\Tests\Integration\SignedInUserTrait;
use App\UseCase\Planning\ListTaskPlanningUseCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * What the task planning tests share: Alice takes the bins out on Mondays and Thursdays, Bob
 * plans nothing. The fixture runs with the handlers off, so no task is made yet; dates are read
 * against the real today.
 */
trait TaskPlanningTestTrait
{
    use LoadFixturesTrait;
    use SignedInUserTrait;

    private int $aliceId;
    private int $bobId;

    private function loadPlanningFixtures(): void
    {
        $this->loadFixtures(TaskPlanningEntryFixtures::class);

        $this->aliceId = $this->getReference(UserFixtures::ALICE, UserDataModel::class)->id ?? 0;
        $this->bobId = $this->getReference(UserFixtures::BOB_DEACTIVATED, UserDataModel::class)->id ?? 0;
    }

    private function seededEntryId(): int
    {
        return $this->getReference(TaskPlanningEntryFixtures::ALICE_BINS_MONDAY_THURSDAY, TaskPlanningEntryDataModel::class)->id ?? 0;
    }

    private function categoryId(string $reference): int
    {
        return $this->getReference($reference, CategoryDataModel::class)->id ?? 0;
    }

    private function homeId(): int
    {
        return $this->categoryId(CategoryFixtures::HOME);
    }

    /** Every day of the week by default, from this week's Monday: today always falls. */
    private function everyDay(string $title = 'Arroser les plantes', ?int $categoryId = null, ?string $startTime = null): SaveTaskPlanningEntryDataInput
    {
        return new SaveTaskPlanningEntryDataInput($title, null, $categoryId, new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::WEEKLY,
            startDate: $this->monday(),
            weekdays: [1, 2, 3, 4, 5, 6, 7],
            startTime: $startTime,
        ));
    }

    private function singleDate(string $day, string $title = 'Déclarer les impôts'): SaveTaskPlanningEntryDataInput
    {
        return new SaveTaskPlanningEntryDataInput($title, 'En ligne.', null, new PlanningScheduleDataInput(rule: PlanningRuleRegistry::ONCE, startDate: $day));
    }

    private function thisWeek(?int $userId = null): TaskPlanningDataOutput
    {
        return $this->useCase(ListTaskPlanningUseCase::class)->execute($this->currentUser($userId ?? $this->aliceId), new ListPlanningDataInput($this->monday(), $this->inDays(6, $this->monday())));
    }

    /**
     * Every task of the account an entry made, read afresh, by planned date.
     *
     * @return list<TaskDataModel>
     */
    private function plannedTasks(?int $userId = null): array
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->getRepository(TaskDataModel::class)->createQueryBuilder('task')
            ->andWhere('task.owner = :owner')
            ->andWhere('task.plannedDate IS NOT NULL')
            ->setParameter('owner', $userId ?? $this->aliceId)
            ->orderBy('task.plannedDate', 'ASC')
            ->addOrderBy('task.id', 'ASC')
            ->getQuery()
            ->getResult();
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

    private function inDays(int $days, ?string $from = null): string
    {
        return (new DateTimeImmutable($from ?? $this->today()))->modify(sprintf('%+d days', $days))->format('Y-m-d');
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
