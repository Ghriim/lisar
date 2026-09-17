<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\User;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Validation\Constraint\User\UsernameAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class UsernameAvailableConstraintTest extends TestCase
{
    public function testItAddsNoViolationWhenTheUsernameIsFree(): void
    {
        self::assertSame([], UsernameAvailableConstraint::validate(null));
    }

    public function testItRejectsAUsernameAlreadyUsed(): void
    {
        $violations = UsernameAvailableConstraint::validate(new UserDataModel());

        self::assertSame(['username' => [UsernameAvailableConstraint::USERNAME_ALREADY_USED]], $violations);
    }

    public function testItKeepsTheViolationsItWasGiven(): void
    {
        $violations = UsernameAvailableConstraint::validate(new UserDataModel(), ['email' => ['whatever']]);

        self::assertArrayHasKey('email', $violations);
        self::assertArrayHasKey('username', $violations);
    }
}
