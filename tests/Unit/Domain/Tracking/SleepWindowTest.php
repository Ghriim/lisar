<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Tracking;

use App\Domain\Tracking\SleepWindow;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * What makes a pair of times into a night: the rule that decides which day the bedtime falls on,
 * and the duration that follows from it.
 */
final class SleepWindowTest extends TestCase
{
    private const int BEDTIME_2330 = (23 * 60) + 30;
    private const int WAKE_UP_0700 = 7 * 60;

    /** The ordinary night: to bed before midnight, so the bedtime is the evening before. */
    public function testABedtimeAfterTheWakeUpTimeIsTheEveningBefore(): void
    {
        $window = SleepWindow::forWakingDay($this->parisDay('2026-09-18'), self::BEDTIME_2330, self::WAKE_UP_0700);

        self::assertSame('2026-09-17 23:30', $window->bedtimeAt->format('Y-m-d H:i'));
        self::assertSame('2026-09-18 07:00', $window->wakeUpAt->format('Y-m-d H:i'));
        self::assertSame(450, SleepWindow::durationInMinutes($window->bedtimeAt, $window->wakeUpAt));
    }

    /** The same night begun after midnight: both moments fall on the waking day. */
    public function testABedtimeBeforeTheWakeUpTimeIsTheSameDay(): void
    {
        $window = SleepWindow::forWakingDay($this->parisDay('2026-09-18'), 60, 9 * 60);

        self::assertSame('2026-09-18 01:00', $window->bedtimeAt->format('Y-m-d H:i'));
        self::assertSame('2026-09-18 09:00', $window->wakeUpAt->format('Y-m-d H:i'));
        self::assertSame(480, SleepWindow::durationInMinutes($window->bedtimeAt, $window->wakeUpAt));
    }

    /**
     * The night the clocks go forward: 23:30 to 07:00 is six and a half hours of sleep, not
     * seven and a half. The duration is elapsed time, which is the truth about the night.
     */
    public function testTheNightTheClocksGoForwardIsAnHourShorter(): void
    {
        $window = SleepWindow::forWakingDay($this->parisDay('2027-03-28'), self::BEDTIME_2330, self::WAKE_UP_0700);

        self::assertSame(390, SleepWindow::durationInMinutes($window->bedtimeAt, $window->wakeUpAt));
    }

    /** And the night they go back is an hour longer, for the same reason. */
    public function testTheNightTheClocksGoBackIsAnHourLonger(): void
    {
        $window = SleepWindow::forWakingDay($this->parisDay('2026-10-25'), self::BEDTIME_2330, self::WAKE_UP_0700);

        self::assertSame(510, SleepWindow::durationInMinutes($window->bedtimeAt, $window->wakeUpAt));
    }

    private function parisDay(string $day): DateTimeImmutable
    {
        return new DateTimeImmutable($day, new DateTimeZone('Europe/Paris'));
    }
}
