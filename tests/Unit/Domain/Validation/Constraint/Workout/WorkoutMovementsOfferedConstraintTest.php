<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\Validation\Constraint\Workout\WorkoutMovementsOfferedConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutMovementsOfferedConstraintTest extends TestCase
{
    public function testEveryIdOfferedIsAccepted(): void
    {
        self::assertSame([], WorkoutMovementsOfferedConstraint::validate([1, 2], [$this->movement(1), $this->movement(2)], 'movementIds'));
    }

    public function testAnIdNotOfferedIsRefusedOnTheFieldNamed(): void
    {
        self::assertSame(
            ['movementId' => [WorkoutMovementsOfferedConstraint::UNAVAILABLE]],
            WorkoutMovementsOfferedConstraint::validate([3], [], 'movementId'),
        );
    }

    /** Several missing ids are one problem, said once. */
    public function testSeveralIdsNotOfferedAreRefusedOnce(): void
    {
        self::assertSame(
            ['movementIds' => [WorkoutMovementsOfferedConstraint::UNAVAILABLE]],
            WorkoutMovementsOfferedConstraint::validate([1, 3, 4], [$this->movement(1)], 'movementIds'),
        );
    }

    private function movement(int $id): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = $id;

        return $movement;
    }
}
