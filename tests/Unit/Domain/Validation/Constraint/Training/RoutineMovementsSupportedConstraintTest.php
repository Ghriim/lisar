<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\Validation\Constraint\Training\RoutineMovementsSupportedConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\RoutineTreeTrait;
use PHPUnit\Framework\TestCase;

final class RoutineMovementsSupportedConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use RoutineTreeTrait;

    /** Reps, with a load or without. */
    public function testAMovementThatTracksRepsFits(): void
    {
        self::assertSame([], RoutineMovementsSupportedConstraint::validate([$this->barbellMovement(), $this->bodyweightMovement()], 'exercises'));
    }

    public function testADistanceOrADurationDoesNotFitYet(): void
    {
        $plank = new MovementDataModel();
        $plank->tracksDuration = true;

        self::assertSame(['exercises' => [RoutineMovementsSupportedConstraint::NOT_SUPPORTED]], RoutineMovementsSupportedConstraint::validate([$this->barbellMovement(), $this->carryMovement()], 'exercises'));
        self::assertSame(['movementId' => [RoutineMovementsSupportedConstraint::NOT_SUPPORTED]], RoutineMovementsSupportedConstraint::validate([$plank], 'movementId'));
    }

    /** Reps over a distance — walking lunges — tracks reps, and a distance all the same. */
    public function testRepsOverADistanceDoNotFitEither(): void
    {
        $lunges = $this->bodyweightMovement();
        $lunges->tracksDistance = true;

        self::assertSame(['exercises' => [RoutineMovementsSupportedConstraint::NOT_SUPPORTED]], RoutineMovementsSupportedConstraint::validate([$lunges], 'exercises'));
    }
}
