<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TodoDataModel;
use App\Domain\DTO\Input\Todo\ListTasksDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Constraint\Todo\TodoUsableConstraint;
use App\Domain\Validation\Validator\Todo\ListTasksValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ListTasksValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** Every filter is optional: none given lists the default TODO. */
    public function testItAcceptsNoFilter(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator()->validate($this->anyCurrentUser(), new ListTasksDataInput(), $this->todoOf($this->anyCurrentUser(), isDefault: true));
    }

    public function testItRejectsATodoThatWasNotFound(): void
    {
        $this->assertRefused(['todoId' => [TodoUsableConstraint::TODO_NOT_FOUND]], new ListTasksDataInput(todoId: 7), null);
    }

    public function testItRejectsSomeoneElsesTodo(): void
    {
        $this->assertRefused(['todoId' => [TodoUsableConstraint::TODO_NOT_FOUND]], new ListTasksDataInput(todoId: 7), $this->todoOf($this->currentUser(2)));
    }

    /** An archived TODO's tasks show nowhere. */
    public function testItRejectsAnArchivedTodo(): void
    {
        $this->assertRefused(
            ['todoId' => [TodoActiveConstraint::TODO_ARCHIVED]],
            new ListTasksDataInput(todoId: 7),
            $this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED),
        );
    }

    public function testItAccumulatesTheShapeAndTheTodo(): void
    {
        $this->assertRefused(
            ['todoId' => ['todo_invalid', TodoUsableConstraint::TODO_NOT_FOUND]],
            new ListTasksDataInput(todoId: -1),
            null,
        );
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, ListTasksDataInput $input, ?TodoDataModel $todo): void
    {
        try {
            $this->validator()->validate($this->anyCurrentUser(), $input, $todo);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListTasksValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function validator(): ListTasksValidator
    {
        return new ListTasksValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }
}
