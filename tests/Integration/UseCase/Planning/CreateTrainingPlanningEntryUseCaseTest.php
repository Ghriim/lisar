<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Provider\Planning\TrainingPlanningEntryProviderGateway;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Constraint\Planning\RoutineUsableConstraint;
use App\Domain\Validation\Validator\Planning\CreateTrainingPlanningEntryValidator;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\CreateTrainingPlanningEntryUseCase;
use App\UseCase\Training\CreateRoutineUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Planning one of the caller's routines on a rule of dates. */
final class CreateTrainingPlanningEntryUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use TrainingPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    public function testItPlansARoutine(): void
    {
        $output = $this->create(new SaveTrainingPlanningEntryDataInput($this->pushId(), new PlanningScheduleDataInput(
            rule: PlanningRuleRegistry::MONTHLY_WEEKDAY,
            startDate: '2026-12-01',
            endDate: '2027-03-31',
            weekdays: [6],
            monthWeeks: [-1, 1],
            startTime: '09:30',
        )));

        self::assertSame([$this->pushId(), 'Push'], [$output->routineId, $output->routineName]);
        self::assertSame([1, -1], $output->schedule->monthWeeks);
        self::assertSame(['2026-12-01', '2027-03-31', '09:30', null], [$output->schedule->startDate, $output->schedule->endDate, $output->schedule->startTime, $output->schedule->durationInMinutes]);

        // Read back.
        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $stored = self::getContainer()->get(TrainingPlanningEntryProviderGateway::class)->findOneById($output->id);
        self::assertSame(PlanningRuleRegistry::MONTHLY_WEEKDAY, $stored?->rule);
        self::assertSame([6], $stored->weekdays);
        self::assertSame(9 * 60 + 30, $stored->startTimeInMinutes);
        self::assertSame($this->aliceId, $stored->owner->id);
    }

    public function testSomeoneElsesRoutineIsAnsweredAsNotFound(): void
    {
        $this->assertRefused(['routineId' => [RoutineUsableConstraint::ROUTINE_NOT_FOUND]], new SaveTrainingPlanningEntryDataInput($this->pushId(), $this->weekly()), $this->bobId);
        $this->assertRefused(['routineId' => [RoutineUsableConstraint::ROUTINE_NOT_FOUND]], new SaveTrainingPlanningEntryDataInput(123456789, $this->weekly()));
    }

    public function testAScheduleThatDoesNotHoldIsRefused(): void
    {
        $this->assertRefused(
            ['schedule.weekdays' => [PlanningScheduleConstraint::WEEKDAYS_REQUIRED], 'schedule.durationInMinutes' => [PlanningScheduleConstraint::DURATION_WITHOUT_START]],
            new SaveTrainingPlanningEntryDataInput($this->pushId(), new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-12-01', durationInMinutes: 60)),
        );
    }

    public function testCreatingStaysWithinItsQueryBudget(): void
    {
        $routineId = $this->useCase(CreateRoutineUseCase::class)->execute($this->currentUser($this->aliceId), new CreateRoutineDataInput('Jambes'))->id;

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(CreateTrainingPlanningEntryUseCase::class)->execute($currentUser, new SaveTrainingPlanningEntryDataInput($routineId, $this->weekly())),
            // The routine, then the INSERT.
            max: 2,
        );
    }

    private function weekly(): PlanningScheduleDataInput
    {
        return new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-12-01', weekdays: [1]);
    }

    private function create(SaveTrainingPlanningEntryDataInput $input): TrainingPlanningEntryDataOutput
    {
        return $this->useCase(CreateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $input);
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, SaveTrainingPlanningEntryDataInput $input, ?int $userId = null): void
    {
        try {
            $this->useCase(CreateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($userId ?? $this->aliceId), $input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateTrainingPlanningEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertEqualsCanonicalizing($violations, $exception->violations);
        }
    }
}
