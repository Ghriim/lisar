<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Gamification;

use App\Domain\Gamification\LevelCurve;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** From level N to level N + 1: 50 × N². */
final class LevelCurveTest extends TestCase
{
    /** @return iterable<string, array{int, int}> */
    public static function levels(): iterable
    {
        yield 'nothing yet' => [0, 1];
        yield 'one short of level 2' => [49, 1];
        yield 'level 2' => [50, 2];
        yield 'one short of level 3' => [249, 2];
        yield 'level 3' => [250, 3];
        yield 'level 4' => [700, 4];
        yield 'one short of level 10' => [14_249, 9];
        yield 'level 10' => [14_250, 10];
    }

    #[DataProvider('levels')]
    public function testExperienceGivesALevel(int $experience, int $level): void
    {
        self::assertSame($level, (new LevelCurve())->levelFor($experience));
    }

    public function testWhatEachLevelTakes(): void
    {
        $curve = new LevelCurve();

        self::assertSame([0, 50, 250, 700, 14_250], array_map($curve->experienceToReach(...), [1, 2, 3, 4, 10]));
        self::assertSame([50, 200, 450], array_map($curve->experienceForNext(...), [1, 2, 3]));
    }
}
