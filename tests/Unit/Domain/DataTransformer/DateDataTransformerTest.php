<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\DataTransformer;

use App\Domain\DataTransformer\DateDataTransformer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateDataTransformerTest extends TestCase
{
    public function testItFormatsAMomentWithItsOffset(): void
    {
        $date = new DateTimeImmutable('2026-09-17T14:32:05+02:00');

        self::assertSame('2026-09-17T14:32:05+02:00', DateDataTransformer::dateToString($date));
    }

    public function testItFormatsACalendarDay(): void
    {
        $date = new DateTimeImmutable('2026-09-17T14:32:05+02:00');

        self::assertSame('2026-09-17', DateDataTransformer::dateToDayString($date));
    }

    public function testItPassesNullThrough(): void
    {
        self::assertNull(DateDataTransformer::dateToString(null));
        self::assertNull(DateDataTransformer::dateToDayString(null));
        self::assertNull(DateDataTransformer::dayStringToDate(null));
        self::assertNull(DateDataTransformer::dayStringToDate(''));
    }

    public function testItParsesADayWithoutATimeOfDay(): void
    {
        $date = DateDataTransformer::dayStringToDate('2026-09-17');

        self::assertNotNull($date);
        self::assertSame('2026-09-17 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    public function testItRefusesAnythingThatIsNotADay(): void
    {
        self::assertNull(DateDataTransformer::dayStringToDate('17/09/2026'));
        self::assertNull(DateDataTransformer::dayStringToDate('2026-09-17T14:32:05+02:00'));
        self::assertNull(DateDataTransformer::dayStringToDate('nope'));
    }
}
