<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Planning;

use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Constraint\Planning\RoutineUsableConstraint;
use App\Domain\Validation\Validator\Planning\CreateTrainingPlanningEntryValidator;
use App\Domain\Validation\Validator\Planning\DeleteTrainingPlanningEntryValidator;
use App\Domain\Validation\Validator\Planning\ListTrainingPlanningValidator;
use App\Domain\Validation\Validator\Planning\UpdateTrainingPlanningEntryValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The four validators of training planning entries: each lets a valid case through and refuses one per rule. */
final class TrainingPlanningValidatorsTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;
    use RoutineTreeTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testCreatingLetsAValidEntryThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CreateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->input(), $this->routineOf($this->anyCurrentUser()));
    }

    public function testCreatingAccumulatesTheRoutineTheShapeAndTheSchedule(): void
    {
        $input = new SaveTrainingPlanningEntryDataInput(1, new PlanningScheduleDataInput(PlanningRuleRegistry::WEEKLY, '2026-11-02', weekdays: [8], startTime: '25:00', durationInMinutes: 30));
        $input2 = new SaveTrainingPlanningEntryDataInput(1, new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_DAY, '2026-11-02'));

        $this->assertRefused(
            CreateTrainingPlanningEntryValidator::ERROR_CODE,
            [
                'schedule.weekdays[0]' => ['weekday_invalid'],
                'schedule.startTime' => ['start_time_invalid'],
                'routineId' => [RoutineUsableConstraint::ROUTINE_NOT_FOUND],
            ],
            fn () => (new CreateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $input, null),
        );
        $this->assertRefused(
            CreateTrainingPlanningEntryValidator::ERROR_CODE,
            ['schedule.monthDays' => [PlanningScheduleConstraint::MONTH_DAYS_REQUIRED]],
            fn () => (new CreateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $input2, $this->routineOf($this->anyCurrentUser())),
        );
    }

    public function testCreatingRefusesAnUnknownRule(): void
    {
        $input = new SaveTrainingPlanningEntryDataInput(1, new PlanningScheduleDataInput('fortnightly', '2026-11-02'));

        $this->assertRefused(
            CreateTrainingPlanningEntryValidator::ERROR_CODE,
            ['schedule.rule' => ['rule_unknown']],
            fn () => (new CreateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $input, $this->routineOf($this->anyCurrentUser())),
        );
    }

    public function testUpdatingLetsAValidEntryThrough(): void
    {
        $this->expectNotToPerformAssertions();

        $routine = $this->routineOf($this->anyCurrentUser());
        (new UpdateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->trainingEntry(1, $routine), $this->input(), $routine);
    }

    /** Someone else's entry: refused on that alone, whatever else the call carries. */
    public function testUpdatingRefusesSomeoneElsesEntry(): void
    {
        $routine = $this->routineOf($this->currentUser(99));

        $this->assertRefused(
            UpdateTrainingPlanningEntryValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new UpdateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->trainingEntry(1, $routine), $this->input(), null),
        );
    }

    public function testUpdatingRefusesARoutineNotTheCallers(): void
    {
        $mine = $this->routineOf($this->anyCurrentUser());

        $this->assertRefused(
            UpdateTrainingPlanningEntryValidator::ERROR_CODE,
            ['routineId' => [RoutineUsableConstraint::ROUTINE_NOT_FOUND]],
            fn () => (new UpdateTrainingPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->trainingEntry(1, $mine), $this->input(), $this->routineOf($this->currentUser(99))),
        );
    }

    public function testDeletingRefusesSomeoneElsesEntry(): void
    {
        (new DeleteTrainingPlanningEntryValidator())->validate($this->anyCurrentUser(), $this->trainingEntry(1, $this->routineOf($this->anyCurrentUser())));

        $this->assertRefused(
            DeleteTrainingPlanningEntryValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new DeleteTrainingPlanningEntryValidator())->validate($this->anyCurrentUser(), $this->trainingEntry(1, $this->routineOf($this->currentUser(99)))),
        );
    }

    public function testListingRefusesMissingDaysAndALongRange(): void
    {
        (new ListTrainingPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-23', '2026-11-29'));

        $this->assertRefused(
            ListTrainingPlanningValidator::ERROR_CODE,
            ['from' => ['from_required']],
            fn () => (new ListTrainingPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('', '2026-11-29')),
        );
        $this->assertRefused(
            ListTrainingPlanningValidator::ERROR_CODE,
            ['to' => [PlanningRangeConstraint::RANGE_INVALID]],
            fn () => (new ListTrainingPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-01', '2027-01-01')),
        );
    }

    private function input(): SaveTrainingPlanningEntryDataInput
    {
        return new SaveTrainingPlanningEntryDataInput(1, $this->weeklySchedule([2, 4], startTime: '18:00', duration: 75));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(string $errorCode, array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame($errorCode, $exception->errorCode);
            self::assertEqualsCanonicalizing($violations, $exception->violations);
        }
    }
}
