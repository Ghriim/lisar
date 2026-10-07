<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\MovementMeasureConstraint;
use PHPUnit\Framework\TestCase;

final class MovementMeasureConstraintTest extends TestCase
{
    public function testRepetitionsAloneAreAMeasure(): void
    {
        self::assertSame([], MovementMeasureConstraint::validate(true, false, false));
    }

    public function testADurationAloneIsAMeasure(): void
    {
        self::assertSame([], MovementMeasureConstraint::validate(false, true, false));
    }

    public function testADistanceAloneIsAMeasure(): void
    {
        self::assertSame([], MovementMeasureConstraint::validate(false, false, true));
    }

    public function testSeveralMeasuresAreAccepted(): void
    {
        self::assertSame([], MovementMeasureConstraint::validate(true, true, true));
    }

    public function testNoMeasureAtAllIsRefused(): void
    {
        self::assertSame(
            ['measure' => [MovementMeasureConstraint::MEASURE_REQUIRED]],
            MovementMeasureConstraint::validate(false, false, false),
        );
    }

    public function testItAddsToTheViolationsAlreadyFound(): void
    {
        self::assertSame(
            ['name' => ['name_required'], 'measure' => [MovementMeasureConstraint::MEASURE_REQUIRED]],
            MovementMeasureConstraint::validate(false, false, false, ['name' => ['name_required']]),
        );
    }
}
