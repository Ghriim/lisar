<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Sleep;

use App\Domain\Tracking\SleepWindow;

/**
 * A night has to last a plausible time. Not a judgement on anyone's sleep — a typo filter: 07:30
 * typed in the bedtime field instead of 19:30 is later in the day than the wake-up time, so it
 * is read as the morning before, and would otherwise record a twenty-three-hour night that never
 * happened.
 *
 * The violation is reported on the wake-up time, because that is the field the person just
 * left and the pair is only ever wrong together.
 */
final readonly class SleepDurationConstraint
{
    public const string SLEEP_TOO_SHORT = 'sleep_too_short';
    public const string SLEEP_TOO_LONG = 'sleep_too_long';

    private const string FIELD = 'wakeUpTime';

    /**
     * @param array<string, list<string>> $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(int $durationInMinutes, array $violations = []): array
    {
        if (SleepWindow::MINIMUM_DURATION_IN_MINUTES > $durationInMinutes) {
            $violations[self::FIELD][] = self::SLEEP_TOO_SHORT;
        }

        if (SleepWindow::MAXIMUM_DURATION_IN_MINUTES < $durationInMinutes) {
            $violations[self::FIELD][] = self::SLEEP_TOO_LONG;
        }

        return $violations;
    }
}
