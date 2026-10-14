<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\User\Admin;

use App\Domain\Exception\ValidationException;
use App\Domain\Registry\User\UserRoleRegistry;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\User\SelfDeactivationConstraint;
use App\Domain\Validation\Validator\User\Admin\DeactivateUserValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeactivateUserValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeactivateUserValidator())->validate($this->currentUser(1, UserRoleRegistry::ADMIN), 7);
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new DeactivateUserValidator())->validate($this->currentUser(1), 7);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesToDeactivateOneself(): void
    {
        $this->assertRefused(['id' => [SelfDeactivationConstraint::CANNOT_DEACTIVATE_YOURSELF]], fn () => (new DeactivateUserValidator())->validate($this->currentUser(7, UserRoleRegistry::ADMIN), 7));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeactivateUserValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
