<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Training;

use App\Domain\DTO\DataModel\Training\WorkoutBlockDataModel;
use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\Validation\Constraint\Training\WorkoutBlockOrderConstraint;
use PHPUnit\Framework\TestCase;

final class WorkoutBlockOrderConstraintTest extends TestCase
{
    public function testEveryBlockInAnyOrderIsAccepted(): void
    {
        self::assertSame([], WorkoutBlockOrderConstraint::validate([3, 1, 2], $this->workout(1, 2, 3)));
    }

    public function testABlockLeftOutIsRefused(): void
    {
        self::assertSame(['blockIds' => [WorkoutBlockOrderConstraint::MISMATCH]], WorkoutBlockOrderConstraint::validate([1, 2], $this->workout(1, 2, 3)));
    }

    public function testABlockOfAnotherWorkoutIsRefused(): void
    {
        self::assertArrayHasKey('blockIds', WorkoutBlockOrderConstraint::validate([1, 2, 9], $this->workout(1, 2, 3)));
    }

    public function testABlockNamedTwiceIsRefused(): void
    {
        self::assertArrayHasKey('blockIds', WorkoutBlockOrderConstraint::validate([1, 1, 2, 3], $this->workout(1, 2, 3)));
    }

    private function workout(int ...$blockIds): WorkoutDataModel
    {
        $workout = new WorkoutDataModel();
        foreach ($blockIds as $id) {
            $block = new WorkoutBlockDataModel();
            $block->id = $id;
            $workout->blocks->add($block);
        }

        return $workout;
    }
}
