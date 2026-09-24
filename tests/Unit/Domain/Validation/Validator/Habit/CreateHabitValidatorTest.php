<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habit;

use App\Domain\DTO\Input\Habit\CreateHabitDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habit\HabitIconRegistry;
use App\Domain\Registry\Habit\HabitSourceRegistry;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use App\Domain\Validation\Validator\Habit\CreateHabitValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The shapes the habit catalogue accepts, and the incoherent ones it refuses. */
final class CreateHabitValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsAManualHabit(): void
    {
        $this->validatorUnderTest()->validate(
            new CreateHabitDataInput('Lire', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL),
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItAcceptsATrackerHabit(): void
    {
        $this->validatorUnderTest()->validate(new CreateHabitDataInput(
            'Marcher',
            HabitIconRegistry::RUN,
            HabitSourceRegistry::TRACKER,
            HabitTrackerRegistry::STEPS,
            10000,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesAnUnknownIcon(): void
    {
        try {
            $this->validatorUnderTest()->validate(
                new CreateHabitDataInput('Lire', 'unicorn', HabitSourceRegistry::MANUAL),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CreateHabitValidator::ERROR_CODE, $exception->errorCode);
            self::assertArrayHasKey('icon', $exception->violations);
        }
    }

    /** A tracker habit that names no tracker and no mark is incoherent — and both are reported at once. */
    public function testItRefusesATrackerHabitWithoutATrackerOrAMark(): void
    {
        try {
            $this->validatorUnderTest()->validate(
                new CreateHabitDataInput('Marcher', HabitIconRegistry::RUN, HabitSourceRegistry::TRACKER),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('tracker_kind_unknown', $exception->violations['trackerKind']);
            self::assertContains('tracker_threshold_required', $exception->violations['trackerThreshold']);
        }
    }

    public function testItRefusesABlankName(): void
    {
        try {
            $this->validatorUnderTest()->validate(
                new CreateHabitDataInput('', HabitIconRegistry::BOOK, HabitSourceRegistry::MANUAL),
            );
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertContains('name_required', $exception->violations['name']);
        }
    }

    private function validatorUnderTest(): CreateHabitValidator
    {
        return new CreateHabitValidator($this->validator);
    }
}
