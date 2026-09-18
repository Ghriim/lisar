<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\DataTransformer;

use App\Domain\DataTransformer\TimeDataTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** The one shape a time of day is accepted in, and what it becomes. */
final class TimeDataTransformerTest extends TestCase
{
    #[DataProvider('wellFormedTimes')]
    public function testItCountsMinutesSinceMidnight(string $time, int $expected): void
    {
        self::assertSame($expected, TimeDataTransformer::timeStringToMinutes($time));
    }

    /** @return iterable<string, array{string, int}> */
    public static function wellFormedTimes(): iterable
    {
        yield 'midnight' => ['00:00', 0];
        yield 'an early rise' => ['07:00', 420];
        yield 'a late bedtime' => ['23:30', 1410];
        yield 'the last minute of the day' => ['23:59', 1439];
    }

    /**
     * Null rather than an exception: a malformed time is something the caller typed, so it has
     * to come back as a violation and not as a failure.
     */
    #[DataProvider('malformedTimes')]
    public function testItRefusesAnythingElse(?string $time): void
    {
        self::assertNull(TimeDataTransformer::timeStringToMinutes($time));
    }

    /** @return iterable<string, array{?string}> */
    public static function malformedTimes(): iterable
    {
        yield 'nothing at all' => [null];
        yield 'an empty string' => [''];
        yield 'an hour that does not exist' => ['24:00'];
        yield 'a minute that does not exist' => ['23:60'];
        yield 'an unpadded hour' => ['7:00'];
        yield 'seconds nobody asked for' => ['07:00:00'];
        yield 'a word' => ['matin'];
        yield 'a dot instead of a colon' => ['07.00'];
    }
}
