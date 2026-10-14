<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Common;

use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class IsOwnerConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testTheOwnerPasses(): void
    {
        self::assertSame([], IsOwnerConstraint::validate($this->currentUser(1), $this->currentUser(1)));
    }

    public function testSomeoneElseIsNotAllowed(): void
    {
        self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], IsOwnerConstraint::validate($this->currentUser(1), $this->currentUser(2)));
    }

    /** Their own, but switched off since: not allowed either. */
    public function testAnInactiveOwnerIsNotAllowed(): void
    {
        self::assertSame(
            ['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            IsOwnerConstraint::validate($this->currentUser(1, isActive: false), $this->currentUser(1)),
        );
    }

    public function testItKeepsWhatWasAlreadyFound(): void
    {
        self::assertSame(
            ['name' => ['name_blank'], 'currentUser' => [IsOwnerConstraint::NOT_ALLOWED]],
            IsOwnerConstraint::validate($this->currentUser(1), $this->currentUser(2), ['name' => ['name_blank']]),
        );
    }
}
