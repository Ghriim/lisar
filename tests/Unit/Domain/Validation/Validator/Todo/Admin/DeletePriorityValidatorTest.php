<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo\Admin;

use App\Domain\DTO\DataModel\Todo\PriorityDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\PriorityDeletableConstraint;
use App\Domain\Validation\Validator\Todo\Admin\DeletePriorityValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeletePriorityValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeletePriorityValidator())->validate($this->currentAdmin(), $this->priority(false), 0);
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new DeletePriorityValidator())->validate($this->anyCurrentUser(), $this->priority(false), 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeletePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesAPriorityTasksCarry(): void
    {
        $this->assertRefused(['id' => [PriorityDeletableConstraint::PRIORITY_IN_USE]], fn () => (new DeletePriorityValidator())->validate($this->currentAdmin(), $this->priority(false), 2));
    }

    public function testItRefusesTheDefaultPriority(): void
    {
        $this->assertRefused(['id' => [PriorityDeletableConstraint::PRIORITY_IS_THE_DEFAULT]], fn () => (new DeletePriorityValidator())->validate($this->currentAdmin(), $this->priority(true), 0));
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $this->assertRefused(['id' => [PriorityDeletableConstraint::PRIORITY_IN_USE, PriorityDeletableConstraint::PRIORITY_IS_THE_DEFAULT]], fn () => (new DeletePriorityValidator())->validate($this->currentAdmin(), $this->priority(true), 2));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeletePriorityValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function priority(bool $isDefault): PriorityDataModel
    {
        $priority = new PriorityDataModel();
        $priority->isDefault = $isDefault;

        return $priority;
    }
}
