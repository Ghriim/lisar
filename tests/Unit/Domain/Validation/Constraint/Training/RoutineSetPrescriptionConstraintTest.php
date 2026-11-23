<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\Validation\Constraint\Training\RoutineSetPrescriptionConstraint;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineSetPrescriptionConstraintTest extends TestCase
{
    use RoutineTreeTrait;

    public function testABarbellSetCarriesRepsAndAShare(): void
    {
        self::assertSame([], RoutineSetPrescriptionConstraint::validate($this->barbellMovement(), 5, 80.0));
    }

    public function testABodyweightSetCarriesRepsAlone(): void
    {
        self::assertSame([], RoutineSetPrescriptionConstraint::validate($this->bodyweightMovement(), 12, null));
    }

    public function testWhatTheMovementCallsForIsRequired(): void
    {
        self::assertSame(
            [
                'reps' => [RoutineSetPrescriptionConstraint::REPS_REQUIRED],
                'percentageOfOneRepMax' => [RoutineSetPrescriptionConstraint::PERCENTAGE_REQUIRED],
            ],
            RoutineSetPrescriptionConstraint::validate($this->barbellMovement(), null, null),
        );
    }

    /** A share of the 1RM on a push-up would be a number nothing reads. */
    public function testAShareOnAMovementWithoutALoadIsRefused(): void
    {
        self::assertSame(
            ['percentageOfOneRepMax' => [RoutineSetPrescriptionConstraint::PERCENTAGE_NOT_TRACKED]],
            RoutineSetPrescriptionConstraint::validate($this->bodyweightMovement(), 12, 70.0),
        );
    }
}
