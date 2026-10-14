<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Todo\Admin;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Todo\CategoryUnusedConstraint;
use App\Domain\Validation\Validator\Todo\Admin\DeleteReferenceCategoryValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeleteReferenceCategoryValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeleteReferenceCategoryValidator())->validate($this->currentAdmin(), 0);
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new DeleteReferenceCategoryValidator())->validate($this->anyCurrentUser(), 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesACategoryTasksSitIn(): void
    {
        $this->assertRefused(['id' => [CategoryUnusedConstraint::CATEGORY_IN_USE]], fn () => (new DeleteReferenceCategoryValidator())->validate($this->currentAdmin(), 3));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteReferenceCategoryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
