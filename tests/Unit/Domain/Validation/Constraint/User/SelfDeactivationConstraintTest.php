<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\User;

use App\Domain\Validation\Constraint\User\SelfDeactivationConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class SelfDeactivationConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAllowsDeactivatingSomeoneElse(): void
    {
        self::assertSame([], SelfDeactivationConstraint::validate(1, $this->currentUser(2)));
    }

    public function testItRejectsDeactivatingYourself(): void
    {
        self::assertSame(
            ['id' => [SelfDeactivationConstraint::CANNOT_DEACTIVATE_YOURSELF]],
            SelfDeactivationConstraint::validate(1, $this->currentUser(1)),
        );
    }

    public function testItKeepsTheViolationsItWasGiven(): void
    {
        $violations = SelfDeactivationConstraint::validate(1, $this->currentUser(1), ['whatever' => ['whatever']]);

        self::assertArrayHasKey('whatever', $violations);
        self::assertArrayHasKey('id', $violations);
    }
}
