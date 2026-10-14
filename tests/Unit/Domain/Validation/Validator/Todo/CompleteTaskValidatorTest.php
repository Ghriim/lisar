<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\TaskDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TaskClosableConstraint;
use App\Domain\Validation\Validator\Todo\CompleteTaskValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class CompleteTaskValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CompleteTaskValidator())->validate($this->anyCurrentUser(), $this->task(false));
    }

    /** Someone else's task: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesTask(): void
    {
        try {
            (new CompleteTaskValidator())->validate($this->currentUser(99), $this->task(false));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CompleteTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesATaskWithAnOpenSubtask(): void
    {
        $this->assertRefused(['id' => [TaskClosableConstraint::TASK_HAS_OPEN_SUBTASKS]], fn () => (new CompleteTaskValidator())->validate($this->anyCurrentUser(), $this->task(true)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CompleteTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function task(bool $withOpenSubtask): TaskDataModel
    {
        $task = new TaskDataModel();
        $task->owner = $this->anyCurrentUser();
        if (true === $withOpenSubtask) {
            $task->subtasks->add(new TaskDataModel());
        }

        return $task;
    }
}
