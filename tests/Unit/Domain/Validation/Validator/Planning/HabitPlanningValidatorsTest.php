<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Planning;

use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveHabitPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\HabitSubscribedConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Validator\Planning\CreateHabitPlanningEntryValidator;
use App\Domain\Validation\Validator\Planning\DeleteHabitPlanningEntryValidator;
use App\Domain\Validation\Validator\Planning\ListHabitPlanningValidator;
use App\Domain\Validation\Validator\Planning\UpdateHabitPlanningEntryValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The four validators of habit planning entries: each lets a valid case through and refuses one per rule. */
final class HabitPlanningValidatorsTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testCreatingLetsAValidEntryThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CreateHabitPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->input(), $this->subscriptionOf($this->anyCurrentUser()));
    }

    public function testCreatingAccumulatesTheHabitTheShapeAndTheSchedule(): void
    {
        $input = new SaveHabitPlanningEntryDataInput(0, new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_WEEKDAY, '2026-11-02', weekdays: [1]));

        $this->assertRefused(
            CreateHabitPlanningEntryValidator::ERROR_CODE,
            [
                'habitId' => ['habit_invalid', HabitSubscribedConstraint::HABIT_NOT_SUBSCRIBED],
                'schedule.monthWeeks' => [PlanningScheduleConstraint::MONTH_WEEKS_REQUIRED],
            ],
            fn () => (new CreateHabitPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $input, null),
        );
    }

    public function testUpdatingLetsAValidEntryThrough(): void
    {
        $this->expectNotToPerformAssertions();

        $subscription = $this->subscriptionOf($this->anyCurrentUser());
        (new UpdateHabitPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->habitEntry(1, $subscription), $this->input(), $subscription);
    }

    /** Someone else's entry: refused on that alone, whatever else the call carries. */
    public function testUpdatingRefusesSomeoneElsesEntry(): void
    {
        $this->assertRefused(
            UpdateHabitPlanningEntryValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new UpdateHabitPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->habitEntry(1, $this->subscriptionOf($this->currentUser(99))), $this->input(), null),
        );
    }

    public function testUpdatingRefusesAHabitNotKept(): void
    {
        $subscription = $this->subscriptionOf($this->anyCurrentUser());
        $dropped = $this->subscriptionOf($this->anyCurrentUser(), 2, 2);
        $dropped->isActive = false;

        $this->assertRefused(
            UpdateHabitPlanningEntryValidator::ERROR_CODE,
            ['habitId' => [HabitSubscribedConstraint::HABIT_NOT_SUBSCRIBED]],
            fn () => (new UpdateHabitPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->habitEntry(1, $subscription), $this->input(), $dropped),
        );
    }

    public function testDeletingRefusesSomeoneElsesEntry(): void
    {
        (new DeleteHabitPlanningEntryValidator())->validate($this->anyCurrentUser(), $this->habitEntry(1, $this->subscriptionOf($this->anyCurrentUser())));

        $this->assertRefused(
            DeleteHabitPlanningEntryValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new DeleteHabitPlanningEntryValidator())->validate($this->anyCurrentUser(), $this->habitEntry(1, $this->subscriptionOf($this->currentUser(99)))),
        );
    }

    public function testListingRefusesMissingDaysAndALongRange(): void
    {
        (new ListHabitPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-23', '2026-11-29'));

        $this->assertRefused(
            ListHabitPlanningValidator::ERROR_CODE,
            ['to' => ['to_invalid']],
            fn () => (new ListHabitPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-23', 'demain')),
        );
        $this->assertRefused(
            ListHabitPlanningValidator::ERROR_CODE,
            ['to' => [PlanningRangeConstraint::RANGE_INVALID]],
            fn () => (new ListHabitPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-29', '2026-11-23')),
        );
    }

    private function input(): SaveHabitPlanningEntryDataInput
    {
        return new SaveHabitPlanningEntryDataInput(1, $this->weeklySchedule([1, 3, 5]));
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
