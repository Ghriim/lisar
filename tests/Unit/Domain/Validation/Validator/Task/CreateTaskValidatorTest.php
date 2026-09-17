<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\PriorityDataModel;
use App\Domain\DTO\DataModel\TaskDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\CreateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\CategoryUsableConstraint;
use App\Domain\Validation\Constraint\Task\ParentTaskUsableConstraint;
use App\Domain\Validation\Constraint\Task\PriorityExistsConstraint;
use App\Domain\Validation\Validator\Task\CreateTaskValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateTaskValidatorTest extends TestCase
{
    private CreateTaskValidator $validator;
    private UserDataModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new CreateTaskValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );

        $this->owner = new UserDataModel();
        $this->owner->id = 1;
    }

    public function testItAcceptsATaskWithNothingButATitle(): void
    {
        $this->validator->validate(new CreateTaskDataInput('Buy milk'), null, null, null, $this->owner);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankTitle(): void
    {
        $this->assertViolatesOn(new CreateTaskDataInput(''), 'title', 'title_required');
    }

    public function testItRejectsATitleLongerThanTheColumn(): void
    {
        $this->assertViolatesOn(new CreateTaskDataInput(str_repeat('a', 256)), 'title', 'title_too_long');
    }

    public function testItRejectsADueDateThatIsNotACalendarDay(): void
    {
        $input = new CreateTaskDataInput('Buy milk', dueDate: '17/09/2026');

        $this->assertViolatesOn($input, 'dueDate', 'due_date_invalid');
    }

    public function testItRejectsATagLongerThanTheColumn(): void
    {
        $input = new CreateTaskDataInput('Buy milk', tags: [str_repeat('a', 33)]);

        $this->assertViolatesOn($input, 'tags[0]', 'tag_too_long');
    }

    public function testItRejectsAPriorityThatDoesNotExist(): void
    {
        $input = new CreateTaskDataInput('Buy milk', priorityId: 404);

        $this->assertViolatesOn($input, 'priorityId', PriorityExistsConstraint::PRIORITY_NOT_FOUND);
    }

    public function testItRejectsSomeoneElsesPersonalCategory(): void
    {
        $otherOwner = new UserDataModel();
        $otherOwner->id = 2;

        $category = new CategoryDataModel();
        $category->owner = $otherOwner;

        try {
            $this->validator->validate(
                new CreateTaskDataInput('Buy milk', categoryId: 9),
                null,
                $category,
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(CategoryUsableConstraint::CATEGORY_NOT_FOUND, $exception->violations['categoryId']);
        }
    }

    public function testItRejectsASubtaskOfASubtask(): void
    {
        $parent = new TaskDataModel();
        $parent->parent = new TaskDataModel();

        try {
            $this->validator->validate(
                new CreateTaskDataInput('Buy milk', parentId: 9),
                null,
                null,
                $parent,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                ParentTaskUsableConstraint::PARENT_TASK_IS_A_SUBTASK,
                $exception->violations['parentId'],
            );
        }
    }

    public function testItAcceptsAPriorityAndACategoryThatWereFound(): void
    {
        $category = new CategoryDataModel();
        $category->owner = null;

        $this->validator->validate(
            new CreateTaskDataInput('Buy milk', priorityId: 1, categoryId: 2),
            new PriorityDataModel(),
            $category,
            null,
            $this->owner,
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(
                new CreateTaskDataInput('', dueDate: 'nope', priorityId: 404, categoryId: 404, parentId: 404),
                null,
                null,
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('dueDate', $exception->violations);
            self::assertArrayHasKey('priorityId', $exception->violations);
            self::assertArrayHasKey('categoryId', $exception->violations);
            self::assertArrayHasKey('parentId', $exception->violations);
        }
    }

    public function testItTidiesTheTagsUp(): void
    {
        $input = new CreateTaskDataInput('Buy milk', tags: ['  urgent  ', 'urgent', '', 'errand']);

        self::assertSame(['urgent', 'errand'], $input->getTagLabels());
    }

    public function testItReadsTheDueDateAsADay(): void
    {
        $input = new CreateTaskDataInput('Buy milk', dueDate: '2026-09-30');

        self::assertSame('2026-09-30 00:00:00', $input->getDueDate()?->format('Y-m-d H:i:s'));
    }

    private function assertViolatesOn(CreateTaskDataInput $input, string $propertyPath, string $errorCode): void
    {
        try {
            $this->validator->validate($input, null, null, null, $this->owner);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey($propertyPath, $exception->violations);
            self::assertContains($errorCode, $exception->violations[$propertyPath]);
        }
    }
}
