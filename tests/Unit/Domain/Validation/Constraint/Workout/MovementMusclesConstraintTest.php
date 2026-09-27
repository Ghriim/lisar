<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Workout;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\Validation\Constraint\Workout\MovementMusclesConstraint;
use PHPUnit\Framework\TestCase;

final class MovementMusclesConstraintTest extends TestCase
{
    public function testAvailableMusclesAreAccepted(): void
    {
        self::assertSame(
            [],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2, 3], [$this->muscle(2), $this->muscle(3)]),
        );
    }

    public function testAPrimaryMuscleAloneIsEnough(): void
    {
        self::assertSame([], MovementMusclesConstraint::validate(1, $this->muscle(1), [], []));
    }

    public function testAMissingPrimaryMuscleIsNotFound(): void
    {
        self::assertSame(
            ['primaryMuscleId' => [MovementMusclesConstraint::PRIMARY_MUSCLE_NOT_FOUND]],
            MovementMusclesConstraint::validate(1, null, [], []),
        );
    }

    public function testAnInactivePrimaryMuscleIsUnavailable(): void
    {
        self::assertSame(
            ['primaryMuscleId' => [MovementMusclesConstraint::PRIMARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(1, $this->muscle(1, false), [], []),
        );
    }

    public function testAPrimaryMuscleInAnInactiveGroupIsUnavailable(): void
    {
        self::assertSame(
            ['primaryMuscleId' => [MovementMusclesConstraint::PRIMARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(1, $this->muscle(1, true, false), [], []),
        );
    }

    public function testAMissingSecondaryMuscleIsNotFound(): void
    {
        self::assertSame(
            ['secondaryMuscleIds' => [MovementMusclesConstraint::SECONDARY_MUSCLE_NOT_FOUND]],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2, 3], [$this->muscle(2)]),
        );
    }

    public function testAnInactiveSecondaryMuscleIsUnavailable(): void
    {
        self::assertSame(
            ['secondaryMuscleIds' => [MovementMusclesConstraint::SECONDARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2], [$this->muscle(2, false)]),
        );
    }

    public function testASecondaryMuscleInAnInactiveGroupIsUnavailable(): void
    {
        self::assertSame(
            ['secondaryMuscleIds' => [MovementMusclesConstraint::SECONDARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2], [$this->muscle(2, true, false)]),
        );
    }

    public function testSeveralUnavailableSecondaryMusclesAreReportedOnce(): void
    {
        self::assertSame(
            ['secondaryMuscleIds' => [MovementMusclesConstraint::SECONDARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2, 3], [$this->muscle(2, false), $this->muscle(3, false)]),
        );
    }

    public function testThePrimaryMuscleIsNeverAlsoSecondary(): void
    {
        self::assertSame(
            ['secondaryMuscleIds' => [MovementMusclesConstraint::PRIMARY_MUSCLE_ALSO_SECONDARY]],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [1, 2], [$this->muscle(1), $this->muscle(2)]),
        );
    }

    public function testItAccumulatesEveryViolation(): void
    {
        $violations = MovementMusclesConstraint::validate(
            1,
            $this->muscle(1, false),
            [1, 2, 3],
            [$this->muscle(1, false), $this->muscle(2, false)],
            null,
            ['name' => ['name_required']],
        );

        self::assertSame(['name_required'], $violations['name']);
        self::assertSame([MovementMusclesConstraint::PRIMARY_MUSCLE_UNAVAILABLE], $violations['primaryMuscleId']);
        self::assertSame(
            [
                MovementMusclesConstraint::SECONDARY_MUSCLE_NOT_FOUND,
                MovementMusclesConstraint::SECONDARY_MUSCLE_UNAVAILABLE,
                MovementMusclesConstraint::PRIMARY_MUSCLE_ALSO_SECONDARY,
            ],
            $violations['secondaryMuscleIds'],
        );
    }

    public function testAPrimaryMuscleRetiredSinceMayStay(): void
    {
        $current = $this->movement($this->muscle(1, false), [$this->muscle(2)]);

        self::assertSame(
            [],
            MovementMusclesConstraint::validate(1, $this->muscle(1, false), [2], [$this->muscle(2)], $current),
        );
    }

    public function testASecondaryMuscleRetiredSinceMayStay(): void
    {
        $current = $this->movement($this->muscle(1), [$this->muscle(2, true, false)]);

        self::assertSame(
            [],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2], [$this->muscle(2, true, false)], $current),
        );
    }

    public function testAMuscleRetiredSinceMaySwitchRole(): void
    {
        $current = $this->movement($this->muscle(1), [$this->muscle(2, false)]);

        self::assertSame(
            [],
            MovementMusclesConstraint::validate(2, $this->muscle(2, false), [1], [$this->muscle(1)], $current),
        );
    }

    public function testARetiredPrimaryMuscleTheMovementDoesNotHaveIsRefused(): void
    {
        $current = $this->movement($this->muscle(1), [$this->muscle(2)]);

        self::assertSame(
            ['primaryMuscleId' => [MovementMusclesConstraint::PRIMARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(4, $this->muscle(4, false), [2], [$this->muscle(2)], $current),
        );
    }

    public function testARetiredSecondaryMuscleTheMovementDoesNotHaveIsRefused(): void
    {
        $current = $this->movement($this->muscle(1), [$this->muscle(2)]);

        self::assertSame(
            ['secondaryMuscleIds' => [MovementMusclesConstraint::SECONDARY_MUSCLE_UNAVAILABLE]],
            MovementMusclesConstraint::validate(1, $this->muscle(1), [2, 4], [$this->muscle(2), $this->muscle(4, false)], $current),
        );
    }

    private function muscle(int $id, bool $isActive = true, bool $isGroupActive = true): MuscleDataModel
    {
        $group = new MuscleGroupDataModel();
        $group->id = 100 + $id;
        $group->name = 'Group '.$id;
        $group->isActive = $isGroupActive;

        $muscle = new MuscleDataModel();
        $muscle->id = $id;
        $muscle->name = 'Muscle '.$id;
        $muscle->muscleGroup = $group;
        $muscle->isActive = $isActive;

        return $muscle;
    }

    /**
     * @param list<MuscleDataModel> $secondaryMuscles
     */
    private function movement(MuscleDataModel $primaryMuscle, array $secondaryMuscles): MovementDataModel
    {
        $movement = new MovementDataModel();
        $movement->id = 10;
        $movement->name = 'Bench press (barbell)';
        $movement->primaryMuscle = $primaryMuscle;
        foreach ($secondaryMuscles as $secondaryMuscle) {
            $movement->secondaryMuscles->add($secondaryMuscle);
        }

        return $movement;
    }
}
