<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Category\CategoryDataModel;
use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\UpdateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Constraint\Todo\TodoUsableConstraint;
use App\Domain\Validation\Validator\Todo\UpdateTaskValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateTaskValidatorTest extends TestCase
{
    use CurrentUserTrait;

    private UpdateTaskValidator $validator;
    private UserDataModel $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UpdateTaskValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
        );

        $this->owner = new UserDataModel();
        $this->owner->id = 1;
    }

    public function testItAcceptsATitleOnly(): void
    {
        $this->validator->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput('Buy oat milk'), null, $this->owner);

        $this->expectNotToPerformAssertions();
    }

    /** Someone else's task: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesTask(): void
    {
        try {
            $this->validator->validate($this->currentUser(99), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput('Buy oat milk'), null, $this->owner);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankTitle(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput(''), null, $this->owner);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('title_required', $exception->violations['title']);
        }
    }

    public function testItRejectsAPriorityOutsideTheRegistry(): void
    {
        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                $this->taskOf($this->anyCurrentUser()),
                new UpdateTaskDataInput('Buy oat milk', priority: 'urgent'),
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('priority_unknown', $exception->violations['priority']);
        }
    }

    public function testItAcceptsAReferenceCategory(): void
    {
        $category = new CategoryDataModel();
        $category->owner = null;

        $this->validator->validate(
            $this->anyCurrentUser(),
            $this->taskOf($this->anyCurrentUser()),
            new UpdateTaskDataInput('Buy oat milk', categoryId: 2),
            $category,
            $this->owner,
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItAccumulatesEveryViolation(): void
    {
        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                $this->taskOf($this->anyCurrentUser()),
                new UpdateTaskDataInput('', dueDate: 'nope', priority: 'urgent', categoryId: 404),
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('dueDate', $exception->violations);
            self::assertArrayHasKey('priority', $exception->violations);
            self::assertArrayHasKey('categoryId', $exception->violations);
        }
    }

    public function testItRefusesATaskInAnArchivedTodo(): void
    {
        $task = $this->taskOf($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED));

        $this->assertMoveRefused(['id' => [TodoActiveConstraint::TODO_ARCHIVED]], $task, false, null);
    }

    public function testItLetsATaskMoveToAnotherActiveTodoOfTheCaller(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput('Buy oat milk', todoId: 2), null, $this->owner, true, $this->todoOf($this->anyCurrentUser(), 2));
    }

    public function testItRefusesAMoveToATodoNotFoundSomeoneElsesOrArchived(): void
    {
        $task = $this->taskOf($this->anyCurrentUser());

        $this->assertMoveRefused(['todoId' => [TodoUsableConstraint::TODO_NOT_FOUND]], $task, true, null);
        $this->assertMoveRefused(['todoId' => [TodoUsableConstraint::TODO_NOT_FOUND]], $task, true, $this->todoOf($this->currentUser(2), 2));
        $this->assertMoveRefused(['todoId' => [TodoActiveConstraint::TODO_ARCHIVED]], $task, true, $this->todoOf($this->anyCurrentUser(), 2, TodoStatusRegistry::ARCHIVED));
    }

    /** @param array<string, list<string>> $violations */
    private function assertMoveRefused(array $violations, TaskDataModel $task, bool $moves, ?TodoDataModel $target): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), $task, new UpdateTaskDataInput('Buy oat milk', todoId: 2), null, $this->owner, $moves, $target);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
