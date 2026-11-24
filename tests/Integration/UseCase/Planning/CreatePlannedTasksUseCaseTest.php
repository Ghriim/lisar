<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\Input\Planning\CreatePlannedTasksDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Validation\Validator\Planning\CreatePlannedTasksValidator;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\CreatePlannedTasksUseCase;
use App\UseCase\Planning\CreateTaskPlanningEntryUseCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** The daily run, replayed on Monday 30 November 2026: the seeded bins fall on it. */
final class CreatePlannedTasksUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use TaskPlanningTestTrait;

    private const string MONDAY = '2026-11-30';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItMakesTheDaysTaskOfEachEntryFallingOnIt(): void
    {
        $output = $this->runOnMonday();

        self::assertSame([self::MONDAY, 1], [$output->day, $output->createdCount]);
        [$task] = $this->plannedTasks();
        self::assertSame(['Sortir les poubelles', 'Jaune le lundi, verte le jeudi.', $this->homeId(), TaskPriorityRegistry::DEFAULT], [$task->title, $task->description, $task->category?->id, $task->priority]);
        self::assertSame([self::MONDAY, self::MONDAY, $this->seededEntryId()], [$task->dueDate?->format('Y-m-d'), $task->plannedDate?->format('Y-m-d'), $task->planningEntry?->id]);
        self::assertNull($task->completedAt);
    }

    /** Replayed, it makes nothing more: one task per date of an entry. */
    public function testItCanBeReplayed(): void
    {
        $this->runOnMonday();

        self::assertSame(0, $this->runOnMonday()->createdCount);
        self::assertCount(1, $this->plannedTasks());
    }

    /** Not a single date — made with its entry — nor a rule ended, not started, or not falling that day. */
    public function testItLeavesOutWhatDoesNotFallThatDay(): void
    {
        $create = fn (PlanningScheduleDataInput $schedule) => $this->useCase(CreateTaskPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), new SaveTaskPlanningEntryDataInput('Autre', null, null, $schedule));
        $create(new PlanningScheduleDataInput(rule: PlanningRuleRegistry::ONCE, startDate: self::MONDAY));
        $create(new PlanningScheduleDataInput(rule: PlanningRuleRegistry::WEEKLY, startDate: '2026-11-02', endDate: '2026-11-29', weekdays: [1]));
        $create(new PlanningScheduleDataInput(rule: PlanningRuleRegistry::WEEKLY, startDate: '2026-12-01', weekdays: [1]));
        $create(new PlanningScheduleDataInput(rule: PlanningRuleRegistry::WEEKLY, startDate: '2026-11-02', weekdays: [2]));

        self::assertSame(1, $this->runOnMonday()->createdCount);
    }

    public function testAMalformedDayIsRefused(): void
    {
        try {
            $this->useCase(CreatePlannedTasksUseCase::class)->execute(new CreatePlannedTasksDataInput('lundi'));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreatePlannedTasksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['day' => ['day_invalid']], $exception->violations);
        }
    }

    public function testRunningStaysWithinItsQueryBudget(): void
    {
        $this->assertQueryCountAtMost(
            fn () => null,
            fn () => $this->runOnMonday(),
            // The entries already made that day, the entries spanning it, then one INSERT per task.
            max: 3,
        );
    }

    private function runOnMonday(): mixed
    {
        return $this->useCase(CreatePlannedTasksUseCase::class)->execute(new CreatePlannedTasksDataInput(self::MONDAY));
    }
}
