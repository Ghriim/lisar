<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habits;

use App\Domain\DTO\Input\Habits\UpdateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habits\HabitIconRegistry;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Habits\UpdateHabitValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateHabitValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsAValidUpdate(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator()->validate($this->currentAdmin(), new UpdateHabitDataInput('Lire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL));
    }

    /** A user's token on the back-office: refused on that alone, however wrong the payload. */
    public function testItRefusesANonAdministrator(): void
    {
        $this->assertRefused(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], fn () => $this->validator()->validate($this->anyCurrentUser(), new UpdateHabitDataInput('', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL)));
    }

    public function testItRefusesAnInvalidPayload(): void
    {
        $this->assertRefused(['name' => ['name_required']], fn () => $this->validator()->validate($this->currentAdmin(), new UpdateHabitDataInput('', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateHabitValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function validator(): UpdateHabitValidator
    {
        return new UpdateHabitValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }
}
