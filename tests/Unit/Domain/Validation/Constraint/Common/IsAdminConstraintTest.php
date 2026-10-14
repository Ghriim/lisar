<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Common;

use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class IsAdminConstraintTest extends TestCase
{
    use CurrentUserTrait;

    public function testAnAdministratorPasses(): void
    {
        self::assertSame([], IsAdminConstraint::validate($this->currentAdmin()));
    }

    public function testAUserIsNotAllowed(): void
    {
        self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], IsAdminConstraint::validate($this->anyCurrentUser()));
    }

    public function testItKeepsWhatWasAlreadyFound(): void
    {
        self::assertSame(
            ['name' => ['name_blank'], 'currentUser' => [IsAdminConstraint::NOT_ALLOWED]],
            IsAdminConstraint::validate($this->anyCurrentUser(), ['name' => ['name_blank']]),
        );
    }
}
