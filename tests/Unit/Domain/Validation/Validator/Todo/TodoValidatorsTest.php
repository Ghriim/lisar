<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\Input\Todo\ListTodosDataInput;
use App\Domain\DTO\Input\Todo\SaveTodoDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Todo\TodoStatusRegistry;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\TodoActiveConstraint;
use App\Domain\Validation\Constraint\Todo\TodoNotDefaultConstraint;
use App\Domain\Validation\Constraint\Todo\TodoTitleAvailableConstraint;
use App\Domain\Validation\Validator\Todo\ArchiveTodoValidator;
use App\Domain\Validation\Validator\Todo\CreateTodoValidator;
use App\Domain\Validation\Validator\Todo\DeleteTodoValidator;
use App\Domain\Validation\Validator\Todo\ListTodosValidator;
use App\Domain\Validation\Validator\Todo\ReactivateTodoValidator;
use App\Domain\Validation\Validator\Todo\UpdateTodoValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The validators of TODOs: each lets a valid case through and refuses one per rule. */
final class TodoValidatorsTest extends TestCase
{
    use CurrentUserTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testListingAcceptsEveryStatusAndNone(): void
    {
        $this->expectNotToPerformAssertions();

        foreach ([null, TodoStatusRegistry::ACTIVE, TodoStatusRegistry::ARCHIVED] as $status) {
            (new ListTodosValidator($this->validator))->validate($this->anyCurrentUser(), new ListTodosDataInput($status));
        }
    }

    public function testListingRefusesAStatusOutsideTheRegistry(): void
    {
        $this->assertRefused(
            ListTodosValidator::ERROR_CODE,
            ['status' => ['status_unknown']],
            fn () => (new ListTodosValidator($this->validator))->validate($this->anyCurrentUser(), new ListTodosDataInput('deleted')),
        );
    }

    public function testCreatingLetsAFreeTitleThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CreateTodoValidator($this->validator))->validate($this->anyCurrentUser(), new SaveTodoDataInput('Maison'), null);
    }

    /** Spaces alone are no title: it is measured trimmed, as it is stored. */
    public function testCreatingRefusesABlankTitle(): void
    {
        $this->assertRefused(
            CreateTodoValidator::ERROR_CODE,
            ['title' => ['title_required']],
            fn () => (new CreateTodoValidator($this->validator))->validate($this->anyCurrentUser(), new SaveTodoDataInput('   '), null),
        );
    }

    public function testCreatingAcceptsATitleOfExactly125CharactersOnceTrimmed(): void
    {
        $this->expectNotToPerformAssertions();

        (new CreateTodoValidator($this->validator))->validate($this->anyCurrentUser(), new SaveTodoDataInput('  '.str_repeat('a', 125).'  '), null);
    }

    public function testCreatingAccumulatesTheLengthAndTheTitleTaken(): void
    {
        $this->assertRefused(
            CreateTodoValidator::ERROR_CODE,
            ['title' => ['title_too_long', TodoTitleAvailableConstraint::TITLE_ALREADY_USED]],
            fn () => (new CreateTodoValidator($this->validator))->validate(
                $this->anyCurrentUser(),
                new SaveTodoDataInput(str_repeat('a', 126)),
                $this->todoOf($this->anyCurrentUser(), 3),
            ),
        );
    }

    public function testRenamingLetsTheDefaultBeRenamed(): void
    {
        $this->expectNotToPerformAssertions();

        $default = $this->todoOf($this->anyCurrentUser(), 3, isDefault: true);
        (new UpdateTodoValidator($this->validator))->validate($this->anyCurrentUser(), $default, new SaveTodoDataInput('Mes tâches'), null);
        (new UpdateTodoValidator($this->validator))->validate($this->anyCurrentUser(), $default, new SaveTodoDataInput('A faire'), $default);
    }

    public function testRenamingRefusesSomeoneElsesTodoOnThatAlone(): void
    {
        $this->assertRefused(
            UpdateTodoValidator::ERROR_CODE,
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            fn () => (new UpdateTodoValidator($this->validator))->validate($this->anyCurrentUser(), $this->todoOf($this->currentUser(99)), new SaveTodoDataInput(''), null),
        );
    }

    public function testRenamingAccumulatesTheArchivedTodoAndTheTitleTaken(): void
    {
        $this->assertRefused(
            UpdateTodoValidator::ERROR_CODE,
            ['id' => [TodoActiveConstraint::TODO_ARCHIVED], 'title' => [TodoTitleAvailableConstraint::TITLE_ALREADY_USED]],
            fn () => (new UpdateTodoValidator($this->validator))->validate(
                $this->anyCurrentUser(),
                $this->todoOf($this->anyCurrentUser(), 3, TodoStatusRegistry::ARCHIVED),
                new SaveTodoDataInput('Maison'),
                $this->todoOf($this->anyCurrentUser(), 4),
            ),
        );
    }

    public function testArchivingAndDeletingLetAnOrdinaryTodoThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new ArchiveTodoValidator())->validate($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser()));
        (new DeleteTodoValidator())->validate($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED));
    }

    public function testArchivingRefusesTheDefault(): void
    {
        $this->assertRefused(
            ArchiveTodoValidator::ERROR_CODE,
            ['id' => [TodoNotDefaultConstraint::TODO_IS_DEFAULT]],
            fn () => (new ArchiveTodoValidator())->validate($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser(), isDefault: true)),
        );
    }

    public function testDeletingRefusesTheDefault(): void
    {
        $this->assertRefused(
            DeleteTodoValidator::ERROR_CODE,
            ['id' => [TodoNotDefaultConstraint::TODO_IS_DEFAULT]],
            fn () => (new DeleteTodoValidator())->validate($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser(), isDefault: true)),
        );
    }

    public function testArchivingDeletingAndReactivatingRefuseSomeoneElsesTodo(): void
    {
        $theirs = $this->todoOf($this->currentUser(99), isDefault: true);

        $this->assertRefused(ArchiveTodoValidator::ERROR_CODE, ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], fn () => (new ArchiveTodoValidator())->validate($this->anyCurrentUser(), $theirs));
        $this->assertRefused(DeleteTodoValidator::ERROR_CODE, ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], fn () => (new DeleteTodoValidator())->validate($this->anyCurrentUser(), $theirs));
        $this->assertRefused(ReactivateTodoValidator::ERROR_CODE, ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], fn () => (new ReactivateTodoValidator())->validate($this->anyCurrentUser(), $theirs));
    }

    public function testReactivatingLetsTheCallersOwnTodoThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new ReactivateTodoValidator())->validate($this->anyCurrentUser(), $this->todoOf($this->anyCurrentUser(), status: TodoStatusRegistry::ARCHIVED));
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
