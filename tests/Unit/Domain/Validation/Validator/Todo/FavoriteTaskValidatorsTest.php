<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TaskFavoritableConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Validator\Todo\FavoriteTaskValidator;
use App\Domain\Validation\Validator\Todo\ListTaskOverviewValidator;
use App\Domain\Validation\Validator\Todo\UnfavoriteTaskValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

/** Starring a task and taking its star back share their rules; the overview has none yet. */
final class FavoriteTaskValidatorsTest extends TestCase
{
    use CurrentUserTrait;

    public function testBothLetARootTaskOfAnActiveTodoThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new FavoriteTaskValidator())->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()));
        (new UnfavoriteTaskValidator())->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()));
    }

    public function testBothRefuseSomeoneElsesTaskOnThatAlone(): void
    {
        $theirs = $this->taskOf($this->currentUser(99));
        $theirs->parent = $this->taskOf($this->currentUser(99));

        $this->assertRefused(FavoriteTaskValidator::ERROR_CODE, ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], fn () => (new FavoriteTaskValidator())->validate($this->anyCurrentUser(), $theirs));
        $this->assertRefused(UnfavoriteTaskValidator::ERROR_CODE, ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], fn () => (new UnfavoriteTaskValidator())->validate($this->anyCurrentUser(), $theirs));
    }

    public function testBothAccumulateASubtaskAndAnArchivedTodo(): void
    {
        $subtask = $this->taskOf($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED));
        $subtask->parent = $this->taskOf($this->anyCurrentUser());
        $violations = ['id' => [TaskFavoritableConstraint::SUBTASK_NOT_FAVORITABLE, TodoActiveConstraint::TODO_ARCHIVED]];

        $this->assertRefused(FavoriteTaskValidator::ERROR_CODE, $violations, fn () => (new FavoriteTaskValidator())->validate($this->anyCurrentUser(), $subtask));
        $this->assertRefused(UnfavoriteTaskValidator::ERROR_CODE, $violations, fn () => (new UnfavoriteTaskValidator())->validate($this->anyCurrentUser(), $subtask));
    }

    public function testTheOverviewLetsAnyCallerThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListTaskOverviewValidator())->validate($this->anyCurrentUser());
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(string $errorCode, array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame($errorCode, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
