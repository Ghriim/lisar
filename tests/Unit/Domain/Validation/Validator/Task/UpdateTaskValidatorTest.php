<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Task;

use App\Domain\DTO\DataModel\CategoryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Task\UpdateTaskDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Task\PriorityExistsConstraint;
use App\Domain\Validation\Validator\Task\UpdateTaskValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateTaskValidatorTest extends TestCase
{
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
        $this->validator->validate(new UpdateTaskDataInput('Buy oat milk'), null, null, $this->owner);

        $this->expectNotToPerformAssertions();
    }

    public function testItRejectsABlankTitle(): void
    {
        try {
            $this->validator->validate(new UpdateTaskDataInput(''), null, null, $this->owner);
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
