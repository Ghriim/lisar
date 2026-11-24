<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Validation\Constraint\Planning;

use App\Domain\Validation\Constraint\Planning\HabitSubscribedConstraint;
use App\Tests\CurrentUserTrait;
use App\Tests\PlanningEntryTrait;
use PHPUnit\Framework\TestCase;

final class HabitSubscribedConstraintTest extends TestCase
{
    use CurrentUserTrait;
    use PlanningEntryTrait;

    private const array REFUSED = ['habitId' => [HabitSubscribedConstraint::HABIT_NOT_SUBSCRIBED]];

    public function testAnActiveSubscriptionOfTheCallersPasses(): void
    {
        self::assertSame([], HabitSubscribedConstraint::validate($this->subscriptionOf($this->anyCurrentUser()), $this->anyCurrentUser()));
    }

    public function testAHabitNotKeptIsRefused(): void
    {
        self::assertSame(self::REFUSED, HabitSubscribedConstraint::validate(null, $this->anyCurrentUser()));
    }

    public function testADroppedHabitIsRefused(): void
    {
        $subscription = $this->subscriptionOf($this->anyCurrentUser());
        $subscription->isActive = false;

        self::assertSame(self::REFUSED, HabitSubscribedConstraint::validate($subscription, $this->anyCurrentUser()));
    }

    public function testAHabitRetiredFromTheCatalogueIsRefused(): void
    {
        $subscription = $this->subscriptionOf($this->anyCurrentUser());
        $subscription->habit->isActive = false;

        self::assertSame(self::REFUSED, HabitSubscribedConstraint::validate($subscription, $this->anyCurrentUser()));
    }

    public function testSomeoneElsesSubscriptionIsRefused(): void
    {
        self::assertSame(self::REFUSED, HabitSubscribedConstraint::validate($this->subscriptionOf($this->currentUser(99)), $this->anyCurrentUser()));
    }

    public function testItAddsToTheViolationsAlreadyFound(): void
    {
        self::assertSame(['schedule.rule' => ['rule_unknown']] + self::REFUSED, HabitSubscribedConstraint::validate(null, $this->anyCurrentUser(), ['schedule.rule' => ['rule_unknown']]));
    }
}
