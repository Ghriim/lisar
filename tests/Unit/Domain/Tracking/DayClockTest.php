<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Tracking;

use App\Domain\Tracking\DayClock;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

use const DATE_ATOM;

/**
 * This class is where a day begins, and it is the one place a timezone is allowed to matter.
 * Everything it decides is wrong by two hours if it confuses the instant with the wall clock.
 */
final class DayClockTest extends TestCase
{
    public function testItKeepsTheInstantUntouched(): void
    {
        $clock = $this->buildClock('2026-09-17 19:07:56');

        // What gets stored is the moment itself, not the wall clock of a timezone.
        self::assertSame('2026-09-17T19:07:56+00:00', $clock->now()->format(DATE_ATOM));
    }

    public function testItCountsTheDayInItsOwnTimezone(): void
    {
        $clock = $this->buildClock('2026-09-17 19:07:56');

        self::assertSame('2026-09-17', $clock->today()->format('Y-m-d'));
    }

    /**
     * Half past eleven in London is half past one the next morning in Paris: the day has already
     * turned, and what is drunk then belongs to it.
     */
    public function testTheDayTurnsAtMidnightInTheConfiguredTimezone(): void
    {
        $clock = $this->buildClock('2026-09-17 23:30:00');

        self::assertSame('2026-09-18', $clock->today()->format('Y-m-d'));
    }

    public function testItShowsAMomentOnTheClockTheDayIsCountedOn(): void
    {
        $clock = $this->buildClock('2026-09-17 12:00:00');

        $shown = $clock->inDisplayZone(new DateTimeImmutable('2026-09-17 19:07:56', new DateTimeZone('UTC')));

        self::assertSame('2026-09-17T21:07:56+02:00', $shown->format(DATE_ATOM));
    }

    public function testItRecognisesTheDayInProgress(): void
    {
        $clock = $this->buildClock('2026-09-17 12:00:00');

        self::assertTrue($clock->isCurrentDay(new DateTimeImmutable('2026-09-17')));
        self::assertFalse($clock->isCurrentDay(new DateTimeImmutable('2026-09-16')));
        self::assertFalse($clock->isCurrentDay(new DateTimeImmutable('2026-09-18')));
    }

    /**
     * A timezone-less column keeps the wall clock it is given and drops the offset, so a moment
     * carried in Europe/Paris comes back two hours early. Storing is therefore always in UTC.
     */
    public function testItStoresAMomentAsAUtcInstant(): void
    {
        $parisMorning = new DateTimeImmutable('2026-09-18 07:12:00', new DateTimeZone('Europe/Paris'));

        $stored = $this->buildClock('2026-09-18 05:52:40')->asStoredInstant($parisMorning);

        self::assertSame('UTC', $stored->getTimezone()->getName());
        self::assertSame('2026-09-18 05:12:00', $stored->format('Y-m-d H:i:s'));
        // The same instant, not a shifted one.
        self::assertSame($parisMorning->getTimestamp(), $stored->getTimestamp());
    }

    private function buildClock(string $utcMoment): DayClock
    {
        return new DayClock(
            new MockClock(new DateTimeImmutable($utcMoment, new DateTimeZone('UTC'))),
            'Europe/Paris',
        );
    }
}
