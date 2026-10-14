<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationPresetDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Tracking\Hydration\HydrationIconRegistry;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Validator\Tracking\Hydration\UpdateHydrationPresetValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateHydrationPresetValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItAcceptsAValidUpdate(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator()->validate($this->currentAdmin(), new UpdateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 250));
    }

    /** A user's token on the back-office: refused on that alone, however wrong the payload. */
    public function testItRefusesANonAdministrator(): void
    {
        $this->assertRefused(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], fn () => $this->validator()->validate($this->anyCurrentUser(), new UpdateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 0)));
    }

    public function testItRefusesAnInvalidPayload(): void
    {
        $this->assertRefused(['volumeInMillilitres' => ['volume_invalid']], fn () => $this->validator()->validate($this->currentAdmin(), new UpdateHydrationPresetDataInput(HydrationIconRegistry::GLASS, 0)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateHydrationPresetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function validator(): UpdateHydrationPresetValidator
    {
        return new UpdateHydrationPresetValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }
}
