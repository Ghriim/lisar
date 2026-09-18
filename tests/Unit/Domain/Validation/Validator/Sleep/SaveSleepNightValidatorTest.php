<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Sleep;

use App\Domain\DTO\Input\Sleep\SaveSleepNightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Validation\Constraint\Sleep\SleepDurationConstraint;
use App\Domain\Validation\Validator\Sleep\SaveSleepNightValidator;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** The shape a night is accepted in, and the four ways it is refused. */
final class SaveSleepNightValidatorTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testItAcceptsANightAcrossMidnight(): void
    {
        $this->validate(new SaveSleepNightDataInput('23:30', '07:00', 4));

        $this->expectNotToPerformAssertions();
    }

    /** Rating oneself is optional: a night with no face is a complete night. */
    public function testItAcceptsANightWithNoMood(): void
    {
        $this->validate(new SaveSleepNightDataInput('23:30', '07:00'));

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesAMissingTime(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('', '07:00'));

        self::assertContains('bedtime_required', $violations['bedtime']);
    }

    /**
     * A missing field reports "required" and a malformed one "invalid", never both: every
     * constraint but NotBlank treats an empty string as valid.
     */
    public function testAMissingTimeIsNotAlsoCalledMalformed(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('', '07:00'));

        self::assertSame(['bedtime_required'], $violations['bedtime']);
    }

    public function testItRefusesAMalformedTime(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('23:30', '7h'));

        self::assertContains('wake_up_time_invalid', $violations['wakeUpTime']);
    }

    /**
     * When a time is malformed there is no duration to judge: reporting one would be reporting a
     * violation about a number nobody typed.
     */
    public function testAMalformedTimeIsNotAlsoCalledAnImplausibleNight(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('23:30', '7h'));

        self::assertSame(['wake_up_time_invalid'], $violations['wakeUpTime']);
    }

    public function testItRefusesAMoodOutsideTheScale(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('23:30', '07:00', 6));

        self::assertContains('mood_rating_invalid', $violations['moodRating']);
    }

    /** Twenty minutes is a pause, not a night. */
    public function testItRefusesANightTooShortToBeOne(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('07:00', '07:20'));

        self::assertContains(SleepDurationConstraint::SLEEP_TOO_SHORT, $violations['wakeUpTime']);
    }

    /**
     * The case the bound exists for: 07:30 typed in the bedtime field instead of 19:30. Being
     * later in the day than the 07:00 wake-up, it is read as the morning before — a
     * twenty-three-hour night, which is exactly the shape of this typo.
     */
    public function testItRefusesANightNobodySleeps(): void
    {
        $violations = $this->violationsOf(new SaveSleepNightDataInput('07:30', '07:00'));

        self::assertContains(SleepDurationConstraint::SLEEP_TOO_LONG, $violations['wakeUpTime']);
    }

    /** Sixteen hours is the bound itself, and the bound is allowed. */
    public function testItAcceptsTheLongestNightItAllows(): void
    {
        $this->validate(new SaveSleepNightDataInput('07:30', '23:30'));

        $this->expectNotToPerformAssertions();
    }

    private function validate(SaveSleepNightDataInput $input): void
    {
        (new SaveSleepNightValidator($this->validator))
            ->validate($input, new DateTimeImmutable('2026-09-18', new DateTimeZone('Europe/Paris')));
    }

    /** @return array<string, list<string>> */
    private function violationsOf(SaveSleepNightDataInput $input): array
    {
        try {
            $this->validate($input);
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(SaveSleepNightValidator::ERROR_CODE, $exception->errorCode);

            return $exception->violations;
        }
    }
}
