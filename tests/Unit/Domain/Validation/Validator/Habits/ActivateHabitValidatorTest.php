<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habits;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Habits\ActivateHabitValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ActivateHabitValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new ActivateHabitValidator())->validate($this->currentAdmin());
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new ActivateHabitValidator())->validate($this->anyCurrentUser());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ActivateHabitValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
