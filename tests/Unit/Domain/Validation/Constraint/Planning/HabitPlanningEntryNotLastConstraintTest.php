<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Planning;

use App\Domain\Validation\Constraint\Planning\HabitPlanningEntryNotLastConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use PHPUnit\Framework\TestCase;

final class HabitPlanningEntryNotLastConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;

    private const array REFUSED = ['id' => [HabitPlanningEntryNotLastConstraint::LAST_HABIT_PLANNING_ENTRY]];

    public function testAnEntryBesideAnotherPasses(): void
    {
        $subscription = $this->subscriptionOf($this->anyCurrentUser());

        self::assertSame([], HabitPlanningEntryNotLastConstraint::validate([$this->habitEntry(1, $subscription), $this->habitEntry(2, $subscription)]));
    }

    public function testTheLastEntryIsRefused(): void
    {
        self::assertSame(self::REFUSED, HabitPlanningEntryNotLastConstraint::validate([$this->habitEntry(1, $this->subscriptionOf($this->anyCurrentUser()))]));
    }

    public function testItAddsToTheViolationsAlreadyFound(): void
    {
        $entry = $this->habitEntry(1, $this->subscriptionOf($this->anyCurrentUser()));

        self::assertSame(
            ['currentUser' => ['not_allowed'], ...self::REFUSED],
            HabitPlanningEntryNotLastConstraint::validate([$entry], ['currentUser' => ['not_allowed']]),
        );
    }
}
