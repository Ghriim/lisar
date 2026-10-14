<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Tracking\Hydration;

use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationPresetDataInput;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Tracking\Hydration\HydrationIconRegistry;
use App\Domain\Validation\Constraint\Common\IsAdminConstraint;
use App\Domain\Validation\Constraint\Common\IsOwnerConstraint;
use App\Domain\Validation\Constraint\Tracking\Hydration\EntryFromTodayConstraint;
use App\Domain\Validation\Validator\Tracking\Hydration\CreateHydrationEntryValidator;
use App\Domain\Validation\Validator\Tracking\Hydration\CreateHydrationPresetValidator;
use App\Domain\Validation\Validator\Tracking\Hydration\UpdateHydrationEntryValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The three shapes the hydration domain accepts, and what it refuses. */
final class HydrationValidatorsTest extends TestCase
{
    use CurrentUserTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsAPlausibleVolume(): void
    {
        (new CreateHydrationEntryValidator($this->validator))->validate($this->currentAdmin(), new CreateHydrationEntryDataInput(250));

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesAVolumeOfNothing(): void
    {
        $this->assertRefusesVolume(0);
    }

    public function testItRefusesAVolumeNobodyDrinksInOneGo(): void
    {
        $this->assertRefusesVolume(9000);
    }

    public function testItRefusesToCorrectAnEntryFromAPastDay(): void
    {
        try {
            (new UpdateHydrationEntryValidator($this->validator))
                ->validate($this->currentAdmin(), $this->hydrationEntryOf($this->currentAdmin()), new UpdateHydrationEntryDataInput(250), false);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateHydrationEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EntryFromTodayConstraint::ENTRY_NOT_FROM_TODAY, $exception->violations['id']);
        }
    }

    /** Someone else's drink: refused on that alone, even one of a past day. */
    public function testItRefusesToCorrectSomeoneElsesEntry(): void
    {
        try {
            (new UpdateHydrationEntryValidator($this->validator))
                ->validate($this->currentUser(99), $this->hydrationEntryOf($this->currentAdmin()), new UpdateHydrationEntryDataInput(250), false);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateHydrationEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsOwnerConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItAcceptsEveryIconTheRegistryKnows(): void
    {
        $validator = new CreateHydrationPresetValidator($this->validator);

        foreach (HydrationIconRegistry::ALL as $icon) {
            $validator->validate($this->currentAdmin(), new CreateHydrationPresetDataInput($icon, 250));
        }

        $this->expectNotToPerformAssertions();
    }

    /** A user's token on the back-office: refused on that alone. */
    public function testItRefusesAPresetFromANonAdministrator(): void
    {
        try {
            (new CreateHydrationPresetValidator($this->validator))
                ->validate($this->anyCurrentUser(), new CreateHydrationPresetDataInput('aquarium', 250));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateHydrationPresetValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame(['currentUser' => [IsAdminConstraint::NOT_ALLOWED]], $exception->violations);
        }
    }

    public function testItRefusesAnIconTheFrontEndsCouldNotDraw(): void
    {
        try {
            (new CreateHydrationPresetValidator($this->validator))
                ->validate($this->currentAdmin(), new CreateHydrationPresetDataInput('aquarium', 250));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('icon_unknown', $exception->violations['icon']);
        }
    }

    private function assertRefusesVolume(int $volumeInMillilitres): void
    {
        try {
            (new CreateHydrationEntryValidator($this->validator))
                ->validate($this->currentAdmin(), new CreateHydrationEntryDataInput($volumeInMillilitres));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('volume_invalid', $exception->violations['volumeInMillilitres']);
        }
    }
}
