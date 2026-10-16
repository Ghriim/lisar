<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\CreateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TaskPriorityRegistry;
use App\Domain\Validation\Constraint\Todo\CategoryUsableConstraint;
use App\Domain\Validation\Constraint\Todo\ParentTaskUsableConstraint;
use App\Domain\Validation\Validator\Todo\CreateTaskValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateTaskValidatorTest extends TestCase
{
    use CurrentUserTrait;

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
        $this->validator->validate($this->anyCurrentUser(), new CreateTaskDataInput('Buy milk'), null, null, $this->owner);

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

    public function testItRejectsAPriorityOutsideTheRegistry(): void
    {
        $input = new CreateTaskDataInput('Buy milk', priority: 'urgent');

        $this->assertViolatesOn($input, 'priority', 'priority_unknown');
    }

    public function testItRejectsSomeoneElsesPersonalCategory(): void
    {
        $otherOwner = new UserDataModel();
        $otherOwner->id = 2;

        $category = new CategoryDataModel();
        $category->owner = $otherOwner;

        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                new CreateTaskDataInput('Buy milk', categoryId: 9),
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
        $parent->owner = $this->anyCurrentUser();
        $parent->parent = new TaskDataModel();

        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                new CreateTaskDataInput('Buy milk', parentId: 9),
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

    public function testItAcceptsAKnownPriorityAndACategoryThatWasFound(): void
    {
        $category = new CategoryDataModel();
        $category->owner = null;

        $this->validator->validate(
            $this->anyCurrentUser(),
            new CreateTaskDataInput('Buy milk', priority: TaskPriorityRegistry::HIGH, categoryId: 2),
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
                $this->anyCurrentUser(),
                new CreateTaskDataInput('', dueDate: 'nope', priority: 'urgent', categoryId: 404, parentId: 404),
                null,
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('dueDate', $exception->violations);
            self::assertArrayHasKey('priority', $exception->violations);
            self::assertArrayHasKey('categoryId', $exception->violations);
            self::assertArrayHasKey('parentId', $exception->violations);
        }
    }

    public function testItReadsTheDueDateAsADay(): void
    {
        $input = new CreateTaskDataInput('Buy milk', dueDate: '2026-09-30');

        self::assertSame('2026-09-30 00:00:00', $input->getDueDate()?->format('Y-m-d H:i:s'));
    }

    private function assertViolatesOn(CreateTaskDataInput $input, string $propertyPath, string $errorCode): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), $input, null, null, $this->owner);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey($propertyPath, $exception->violations);
            self::assertContains($errorCode, $exception->violations[$propertyPath]);
        }
    }
}
