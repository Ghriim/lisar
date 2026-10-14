<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Training\ListMuscleGroupsForAdminValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class ListMuscleGroupsForAdminValidatorTest extends TestCase
{
    use CurrentUserTrait;

    /** No rule yet: nothing is refused. */
    public function testItRefusesNothing(): void
    {
        $this->expectNotToPerformAssertions();

        (new ListMuscleGroupsForAdminValidator())->validate($this->currentAdmin());
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new ListMuscleGroupsForAdminValidator())->validate($this->anyCurrentUser());
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(ListMuscleGroupsForAdminValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }
}
