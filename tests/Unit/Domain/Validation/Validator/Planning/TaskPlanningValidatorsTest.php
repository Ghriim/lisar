<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Planning;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\CreatePlannedTasksDataInput;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Input\Planning\PlanningScheduleDataInput;
use App\Domain\DTO\Input\Planning\SaveTaskPlanningEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Planning\PlanningRuleRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningRangeConstraint;
use App\Domain\Validation\Constraint\Planning\PlanningScheduleConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Validator\Planning\CreatePlannedTasksValidator;
use App\Domain\Validation\Validator\Planning\CreateTaskPlanningEntryValidator;
use App\Domain\Validation\Validator\Planning\DeleteTaskPlanningEntryValidator;
use App\Domain\Validation\Validator\Planning\ListTaskPlanningValidator;
use App\Domain\Validation\Validator\Planning\UpdateTaskPlanningEntryValidator;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The validators of task planning entries and of the daily run: each lets a valid case through and refuses one per rule. */
final class TaskPlanningValidatorsTest extends TestCase
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

        (new CreateTaskPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->input(), null);
        (new CreateTaskPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->input(categoryId: 3), $this->categoryOf(null));
    }

    public function testCreatingAccumulatesTheTitleTheCategoryAndTheSchedule(): void
    {
        $input = new SaveTaskPlanningEntryDataInput('', null, 3, new PlanningScheduleDataInput(PlanningRuleRegistry::MONTHLY_DAY, '2026-11-02'));

        $this->assertRefused(
            CreateTaskPlanningEntryValidator::ERROR_CODE,
            [
                'title' => ['title_required'],
                'categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND],
                'schedule.monthDays' => [PlanningScheduleConstraint::MONTH_DAYS_REQUIRED],
            ],
            fn () => (new CreateTaskPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $input, $this->categoryOf($this->currentUser(99))),
        );
    }

    public function testUpdatingLetsAValidEntryThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new UpdateTaskPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->taskEntry(1, $this->anyCurrentUser()), $this->input(), null);
    }

    /** Someone else's entry: refused on that alone, whatever else the call carries. */
    public function testUpdatingRefusesSomeoneElsesEntry(): void
    {
        $this->assertRefused(
            UpdateTaskPlanningEntryValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new UpdateTaskPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->taskEntry(1, $this->currentUser(99)), new SaveTaskPlanningEntryDataInput(), null),
        );
    }

    public function testUpdatingRefusesACategoryNotUsable(): void
    {
        $this->assertRefused(
            UpdateTaskPlanningEntryValidator::ERROR_CODE,
            ['categoryId' => [CategoryUsableConstraint::CATEGORY_NOT_FOUND]],
            fn () => (new UpdateTaskPlanningEntryValidator($this->validator))->validate($this->anyCurrentUser(), $this->taskEntry(1, $this->anyCurrentUser()), $this->input(categoryId: 3), null),
        );
    }

    public function testDeletingRefusesSomeoneElsesEntry(): void
    {
        (new DeleteTaskPlanningEntryValidator())->validate($this->anyCurrentUser(), $this->taskEntry(1, $this->anyCurrentUser()));

        $this->assertRefused(
            DeleteTaskPlanningEntryValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new DeleteTaskPlanningEntryValidator())->validate($this->anyCurrentUser(), $this->taskEntry(1, $this->currentUser(99))),
        );
    }

    public function testListingRefusesALongRange(): void
    {
        (new ListTaskPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-23', '2026-11-29'));

        $this->assertRefused(
            ListTaskPlanningValidator::ERROR_CODE,
            ['to' => [PlanningRangeConstraint::RANGE_INVALID]],
            fn () => (new ListTaskPlanningValidator($this->validator))->validate($this->anyCurrentUser(), new ListPlanningDataInput('2026-11-29', '2026-11-23')),
        );
    }

    public function testTheDailyRunRefusesAMalformedDay(): void
    {
        (new CreatePlannedTasksValidator($this->validator))->validate(new CreatePlannedTasksDataInput());
        (new CreatePlannedTasksValidator($this->validator))->validate(new CreatePlannedTasksDataInput('2026-11-30'));

        $this->assertRefused(
            CreatePlannedTasksValidator::ERROR_CODE,
            ['day' => ['day_invalid']],
            fn () => (new CreatePlannedTasksValidator($this->validator))->validate(new CreatePlannedTasksDataInput('lundi')),
        );
    }

    private function input(?int $categoryId = null): SaveTaskPlanningEntryDataInput
    {
        return new SaveTaskPlanningEntryDataInput('Sortir les poubelles', null, $categoryId, $this->weeklySchedule([1, 4]));
    }

    /** A category: a reference one with no owner, or `$owner`'s own. */
    private function categoryOf(?UserDataModel $owner): CategoryDataModel
    {
        $category = new CategoryDataModel();
        $category->id = 3;
        $category->label = 'Maison';
        $category->owner = $owner;

        return $category;
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
