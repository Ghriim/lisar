<?php

declare(strict_types=1);

namespace App\Domain\Validation\Validator\Sleep;

use App\Domain\DataTransformer\TimeDataTransformer;
use App\Domain\DTO\Input\Sleep\SaveSleepNightDataInput;
use App\Domain\Exception\ValidationException;
use App\Domain\Tracking\SleepWindow;
use App\Domain\Validation\Constraint\Sleep\SleepDurationConstraint;
use App\Domain\Validation\Validator\AbstractBaseValidator;
use DateTimeImmutable;

/**
 * @extends AbstractBaseValidator<SaveSleepNightDataInput>
 */
final readonly class SaveSleepNightValidator extends AbstractBaseValidator
{
    public const string ERROR_CODE = 'save_sleep_night_invalid';

    /**
     * @param DateTimeImmutable $wakingDay the day in progress, which is the day being written to
     *
     * @throws ValidationException
     */
    public function validate(SaveSleepNightDataInput $input, DateTimeImmutable $wakingDay): void
    {
        $violations = $this->getViolations($input);

        $bedtimeInMinutes = TimeDataTransformer::timeStringToMinutes($input->bedtime);
        $wakeUpInMinutes = TimeDataTransformer::timeStringToMinutes($input->wakeUpTime);

        // The duration can only be judged once both times are well-formed. When they are not,
        // saying so is the only useful thing to say: a duration computed from a malformed time
        // would report a second violation about a number nobody typed.
        if (null !== $bedtimeInMinutes && null !== $wakeUpInMinutes) {
            $window = SleepWindow::forWakingDay($wakingDay, $bedtimeInMinutes, $wakeUpInMinutes);

            $violations = SleepDurationConstraint::validate(
                SleepWindow::durationInMinutes($window->bedtimeAt, $window->wakeUpAt),
                $violations,
            );
        }

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }
    }
}
