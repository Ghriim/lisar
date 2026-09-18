<?php

declare(strict_types=1);

namespace App\Domain\Tracking;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Where a day starts, and therefore what "today" means — for every tracker, not just one.
 * Hydration, weight and whatever follows all need the same answer, and must not each have their
 * own.
 *
 * The timezone is fixed for everyone for now: the profile stores none, and guessing from the
 * browser would make the server trust a value it cannot check. The day lisar has users in
 * another timezone, this is the one class to reopen.
 *
 * It draws a line the rest of the domain must not cross: **a moment is stored as the instant it
 * is**, in UTC, and the timezone is used only to decide which day that instant falls on and how
 * to show it. Storing the wall clock of Paris in a column that has no timezone is how a moment
 * comes back two hours wrong.
 */
final readonly class DayClock
{
    public function __construct(
        private ClockInterface $clock,
        #[Autowire('%tracking_timezone%')]
        private string $timezone,
    ) {
    }

    /** The instant, in the form it is stored: unambiguous. */
    public function now(): DateTimeImmutable
    {
        return $this->asStoredInstant($this->clock->now());
    }

    /**
     * The same instant in UTC — the only form a timezone-less `DATETIME` column may be given.
     *
     * Handing such a column a moment carried in `Europe/Paris` writes its wall clock and loses
     * the offset, so the same moment comes back two hours earlier. Anything building a moment
     * from a day or from a local time passes it through here first.
     */
    public function asStoredInstant(DateTimeInterface $moment): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($moment)->setTimezone(new DateTimeZone('UTC'));
    }

    /** Today, as a calendar day with no time of day, in the timezone days are counted in. */
    public function today(): DateTimeImmutable
    {
        return $this->inDisplayZone($this->clock->now())->setTime(0, 0);
    }

    /** Whether that calendar day is the one in progress. */
    public function isCurrentDay(DateTimeInterface $day): bool
    {
        return $day->format('Y-m-d') === $this->today()->format('Y-m-d');
    }

    /**
     * The same instant, read in the timezone the application counts days in — which is the wall
     * clock a person expects to see next to what they logged.
     */
    public function inDisplayZone(DateTimeInterface $moment): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($moment)->setTimezone(new DateTimeZone($this->timezone));
    }
}
