<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Training;

use App\Domain\Training\PrescribedLoad;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrescribedLoadTest extends TestCase
{
    use RoutineTreeTrait;

    /** @return iterable<string, array{float, float, bool, float}> */
    public static function loads(): iterable
    {
        yield 'a round share' => [80.0, 100.0, false, 80.0];
        yield 'down to the kilogram' => [80.0, 92.0, false, 73.0];
        yield 'down, never up, past the half' => [75.0, 99.0, false, 74.0];
        yield 'a half share' => [72.5, 100.0, false, 72.0];
        yield 'down to the half kilogram, unilateral' => [80.0, 31.0, true, 24.5];
        yield 'a whole kilogram, unilateral' => [80.0, 30.0, true, 24.0];
        // 70 % of 104.2857… is 73 to the float's last digit: a hair under is not a kilogram less.
        yield 'a float a hair under a step' => [70.0, 730 / 7, false, 73.0];
    }

    #[DataProvider('loads')]
    public function testItRoundsTheShareDown(float $percentage, float $oneRepMax, bool $isUnilateral, float $expected): void
    {
        self::assertSame($expected, (new PrescribedLoad())->of($this->barbellMovement(isUnilateral: $isUnilateral), $percentage, $oneRepMax));
    }

    /** Never done with a load: nothing to work it out from yet. */
    public function testWithoutAnEstimatedOneRepMaxThereIsNoLoad(): void
    {
        self::assertNull((new PrescribedLoad())->of($this->barbellMovement(), 80.0, null));
    }

    /** A movement without a load carries no share. */
    public function testWithoutAShareThereIsNoLoad(): void
    {
        self::assertNull((new PrescribedLoad())->of($this->bodyweightMovement(), null, 100.0));
    }
}
