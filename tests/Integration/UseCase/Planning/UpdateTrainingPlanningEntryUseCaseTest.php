<?php

declare(strict_types=1);

namespace App\Tests\Integration\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\DTO\Input\Training\CreateRoutineDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Validator\Planning\UpdateTrainingPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\Tests\Integration\QueryCountTrait;
use App\UseCase\Planning\UpdateTrainingPlanningEntryUseCase;
use App\UseCase\Training\CreateRoutineUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** A training entry replaced whole. */
final class UpdateTrainingPlanningEntryUseCaseTest extends KernelTestCase
{
    use QueryCountTrait;
    use TrainingPlanningTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPlanningFixtures();
    }

    /** Another routine, another rule: what the new rule does not read goes. */
    public function testItReplacesTheEntryWhole(): void
    {
        $legs = $this->useCase(CreateRoutineUseCase::class)->execute($this->currentUser($this->aliceId), new CreateRoutineDataInput('Jambes'));

        $output = $this->update(new SaveTrainingPlanningEntryDataInput($legs->id, new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_DAY, '2026-12-01', monthDays: [15])));

        self::assertSame([$this->seededEntryId(), $legs->id, 'Jambes'], [$output->id, $output->routineId, $output->routineName]);
        self::assertSame([PlanningRuleRegistry::MONTHLY_DAY, [], [15], null, null], [$output->schedule->rule, $output->schedule->weekdays, $output->schedule->monthDays, $output->schedule->startTime, $output->schedule->durationInMinutes]);
        self::assertSame($legs->id, $this->thisWeekEntry()->routineId);
    }

    public function testSomeoneElsesEntryIsRefused(): void
    {
        try {
            $this->useCase(UpdateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->bobId), $this->seededEntryId(), $this->valid());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testAScheduleThatDoesNotHoldIsRefused(): void
    {
        try {
            $this->update(new SaveTrainingPlanningEntryDataInput($this->pushId(), new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-12-01', '2026-11-01', [1])));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateTrainingPlanningEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['schedule.endDate' => [PlanningScheduleConstraint::END_BEFORE_START]], $exception->violations);
        }
    }

    public function testAnUnknownEntryIsNotFound(): void
    {
        $this->expectException(DataModelNotFoundException::class);

        $this->useCase(UpdateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), 123456789, $this->valid());
    }

    public function testUpdatingStaysWithinItsQueryBudget(): void
    {
        $entryId = $this->seededEntryId();

        $this->assertQueryCountAtMost(
            fn () => $this->currentUser($this->aliceId),
            fn (UserDataModel $currentUser) => $this->useCase(UpdateTrainingPlanningEntryUseCase::class)->execute($currentUser, $entryId, $this->valid()),
            // The entry with its routine — the same one, not read again — then the UPDATE.
            max: 2,
        );
    }

    private function valid(): SaveTrainingPlanningEntryDataInput
    {
        return new SaveTrainingPlanningEntryDataInput($this->pushId(), new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02', weekdays: [1, 3]));
    }

    private function update(SaveTrainingPlanningEntryDataInput $input): TrainingPlanningEntryDataOutput
    {
        return $this->useCase(UpdateTrainingPlanningEntryUseCase::class)->execute($this->currentUser($this->aliceId), $this->seededEntryId(), $input);
    }

    /** Read back through a fresh request's worth of loading. */
    private function thisWeekEntry(): TrainingPlanningEntryDataOutput
    {
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return $this->thisWeek()->entries[0];
    }
}
