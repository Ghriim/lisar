<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutSetWeightPresentConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutSetWeightPresentConstraintTest extends TestCase
{
    public function testASetStillToDoMayWaitForItsLoad(): void
    {
        self::assertSame([], WorkoutSetWeightPresentConstraint::validate($this->movement(weight: true), null, false));
    }

    public function testADoneSetCarriesItsLoad(): void
    {
        self::assertSame(
            ['weightInKilograms' => [WorkoutSetWeightPresentConstraint::WEIGHT_REQUIRED]],
            WorkoutSetWeightPresentConstraint::validate($this->movement(weight: true), null, true),
        );
        self::assertSame([], WorkoutSetWeightPresentConstraint::validate($this->movement(weight: true), 0.0, true));
    }

    /** A movement without a load asks for none, done or not. */
    public function testAMovementWithoutALoadAsksForNone(): void
    {
        self::assertSame([], WorkoutSetWeightPresentConstraint::validate($this->movement(weight: false), null, true));
    }

    private function movement(bool $weight): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->tracksReps = true;
        $movement->tracksWeight = $weight;

        return $movement;
    }
}
