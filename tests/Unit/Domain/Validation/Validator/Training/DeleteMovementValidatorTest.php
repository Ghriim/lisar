<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Training;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Training\MovementUnusedConstraint;
use App\Domain\Validation\Validator\Training\DeleteMovementValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeleteMovementValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeleteMovementValidator())->validate($this->currentAdmin(), 0);
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesANonAdministrator(): void
    {
        try {
            (new DeleteMovementValidator())->validate($this->anyCurrentUser(), 0);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesAMovementAWorkoutUses(): void
    {
        $this->assertRefused(['id' => [MovementUnusedConstraint::IN_USE]], fn () => (new DeleteMovementValidator())->validate($this->currentAdmin(), 2));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteMovementValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
