<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo;

use App\Domain\DTO\DataModel\Todo\CategoryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Todo\UpdateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Todo\PriorityExistsConstraint;
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
        $this->validator->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput('Buy oat milk'), null, null, $this->owner);

        $this->expectNotToPerformAssertions();
    }

    /** Someone else's task: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesTask(): void
    {
        try {
            $this->validator->validate($this->currentUser(99), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput('Buy oat milk'), null, null, $this->owner);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRejectsABlankTitle(): void
    {
        try {
            $this->validator->validate($this->anyCurrentUser(), $this->taskOf($this->anyCurrentUser()), new UpdateTaskDataInput(''), null, null, $this->owner);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateTaskValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains('title_required', $exception->violations['title']);
        }
    }

    public function testItRejectsAPriorityThatDoesNotExist(): void
    {
        try {
            $this->validator->validate(
                $this->anyCurrentUser(),
                $this->taskOf($this->anyCurrentUser()),
                new UpdateTaskDataInput('Buy oat milk', priorityId: 404),
                null,
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains(
                PriorityExistsConstraint::PRIORITY_NOT_FOUND,
                $exception->violations['priorityId'],
            );
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
            null,
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
                new UpdateTaskDataInput('', dueDate: 'nope', priorityId: 404, categoryId: 404),
                null,
                null,
                $this->owner,
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('title', $exception->violations);
            self::assertArrayHasKey('dueDate', $exception->violations);
            self::assertArrayHasKey('priorityId', $exception->violations);
            self::assertArrayHasKey('categoryId', $exception->violations);
        }
    }
}
