<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Validator\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Registry\Habits\HabitSourceRegistry;
use App\Domain\Validation\Constraint\Habits\HabitManualConstraint;
use App\Domain\Validation\Validator\Habits\CompleteHabitValidator;
use App\Tests\CurrentUserTrait;
use PHPUnit\Framework\TestCase;

final class CompleteHabitValidatorTest extends TestCase
{
    use CurrentUserTrait;

    public function testItLetsAValidCaseThrough(): void
    {
        $this->expectNotToPerformAssertions();

        (new CompleteHabitValidator())->validate($this->anyCurrentUser(), $this->habit(HabitSourceRegistry::MANUAL));
    }

    public function testItRefusesATrackerHabit(): void
    {
        $this->assertRefused(['habit' => [HabitManualConstraint::NOT_MANUAL]], fn () => (new CompleteHabitValidator())->validate($this->anyCurrentUser(), $this->habit(HabitSourceRegistry::TRACKER)));
    }

    /** @param array<string, list<string>> $violations */
    private function assertRefused(array $violations, callable $validate): void
    {
        try {
            $validate();
            self::fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            self::assertSame(CompleteHabitValidator::ERROR_CODE, $exception->errorCode);
            self::assertSame($violations, $exception->violations);
        }
    }

    private function habit(string $sourceKind): HabitDataModel
    {
        $habit = new HabitDataModel();
        $habit->sourceKind = $sourceKind;

        return $habit;
    }
}
