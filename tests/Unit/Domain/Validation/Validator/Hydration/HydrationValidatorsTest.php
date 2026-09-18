<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Hydration;

use App\Domain\DTO\Input\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Input\Hydration\CreateHydrationPresetDataInput;
use App\Domain\DTO\Input\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Hydration\HydrationIconRegistry;
use App\Domain\Validation\Constraint\Hydration\EntryFromTodayConstraint;
use App\Domain\Validation\Validator\Hydration\CreateHydrationEntryValidator;
use App\Domain\Validation\Validator\Hydration\CreateHydrationPresetValidator;
use App\Domain\Validation\Validator\Hydration\UpdateHydrationEntryValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The three shapes the hydration domain accepts, and what it refuses. */
final class HydrationValidatorsTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsAPlausibleVolume(): void
    {
        (new CreateHydrationEntryValidator($this->validator))->validate(new CreateHydrationEntryDataInput(250));

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
                ->validate(new UpdateHydrationEntryDataInput(250), false);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(UpdateHydrationEntryValidator::ERROR_CODE, $exception->errorCode);
            self::assertContains(EntryFromTodayConstraint::ENTRY_NOT_FROM_TODAY, $exception->violations['id']);
        }
    }

    public function testItAcceptsEveryIconTheRegistryKnows(): void
    {
        $validator = new CreateHydrationPresetValidator($this->validator);

        foreach (HydrationIconRegistry::ALL as $icon) {
            $validator->validate(new CreateHydrationPresetDataInput($icon, 250));
        }

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesAnIconTheFrontEndsCouldNotDraw(): void
    {
        try {
            (new CreateHydrationPresetValidator($this->validator))
                ->validate(new CreateHydrationPresetDataInput('aquarium', 250));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('icon_unknown', $exception->violations['icon']);
        }
    }

    private function assertRefusesVolume(int $volumeInMillilitres): void
    {
        try {
            (new CreateHydrationEntryValidator($this->validator))
                ->validate(new CreateHydrationEntryDataInput($volumeInMillilitres));
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('volume_invalid', $exception->violations['volumeInMillilitres']);
        }
    }
}
