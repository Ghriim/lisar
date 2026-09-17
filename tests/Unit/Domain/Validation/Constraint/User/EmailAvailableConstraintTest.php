<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\User;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\Validation\Constraint\User\EmailAvailableConstraint;
use PHPUnit\Framework\TestCase;

final class EmailAvailableConstraintTest extends TestCase
{
    public function testItAddsNoViolationWhenTheEmailIsFree(): void
    {
        self::assertSame([], EmailAvailableConstraint::validate(null));
    }

    public function testItRejectsAnEmailAlreadyUsed(): void
    {
        $violations = EmailAvailableConstraint::validate(new UserDataModel());

        self::assertSame(['email' => [EmailAvailableConstraint::EMAIL_ALREADY_USED]], $violations);
    }

    public function testItKeepsTheViolationsItWasGiven(): void
    {
        $violations = EmailAvailableConstraint::validate(new UserDataModel(), ['username' => ['whatever']]);

        self::assertArrayHasKey('username', $violations);
        self::assertArrayHasKey('email', $violations);
    }
}
