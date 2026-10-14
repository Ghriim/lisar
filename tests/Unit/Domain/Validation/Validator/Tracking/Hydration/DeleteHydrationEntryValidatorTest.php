<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Tracking\Hydration\EntryFromTodayConstraint;
use App\Domain\Validation\Validator\Tracking\Hydration\DeleteHydrationEntryValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class DeleteHydrationEntryValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new DeleteHydrationEntryValidator())->validate($this->anyCurrentUser(), $this->hydrationEntryOf($this->anyCurrentUser()), true);
    }

    /** Someone else's entry: refused on that alone, whatever else the call carries. */
    public function testItRefusesSomeoneElsesEntry(): void
    {
        try {
            (new DeleteHydrationEntryValidator())->validate($this->currentUser(99), $this->hydrationEntryOf($this->anyCurrentUser()), true);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteHydrationEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesAnEntryOfAnotherDay(): void
    {
        $this->assertRefused(['id' => [EntryFromTodayConstraint::ENTRY_NOT_FROM_TODAY]], fn () => (new DeleteHydrationEntryValidator())->validate($this->anyCurrentUser(), $this->hydrationEntryOf($this->anyCurrentUser()), false));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(DeleteHydrationEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }
}
