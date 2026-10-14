<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Validation\Constraint\Habits\HabitManualConstraint;
use PHPUnit\Framework\TestCase;

final class HabitManualConstraintTest extends TestCase
{
    public function testAManualHabitPasses(): void
    {
        self::assertSame([], HabitManualConstraint::validate($this->habit(HabitSourceRegistry::MANUAL)));
    }

    public function testATrackerHabitIsRefused(): void
    {
        self::assertSame(['habit' => [HabitManualConstraint::NOT_MANUAL]], HabitManualConstraint::validate($this->habit(HabitSourceRegistry::TRACKER)));
    }

    public function testItKeepsWhatWasAlreadyFound(): void
    {
        $violations = HabitManualConstraint::validate($this->habit(HabitSourceRegistry::TRACKER), ['name' => ['habit_name_blank']]);

        self::assertSame(['name' => ['habit_name_blank'], 'habit' => [HabitManualConstraint::NOT_MANUAL]], $violations);
    }

    private function habit(string $sourceKind): HabitDataModel
    {
        $habit = new HabitDataModel();
        $habit->sourceKind = $sourceKind;

        return $habit;
    }
}
