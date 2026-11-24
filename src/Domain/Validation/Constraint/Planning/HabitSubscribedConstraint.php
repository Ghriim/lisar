<?php

declare(strict_types=1);

namespace App\Domain\Validation\Constraint\Planning;

use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;

/**
 * A habit entry says which days of a habit the caller keeps: an active subscription of theirs, to
 * a habit still in the catalogue. A habit not kept, or no longer offered, is answered exactly like
 * one that does not exist.
 */
final readonly class HabitSubscribedConstraint
{
    public const string HABIT_NOT_SUBSCRIBED = 'habit_not_subscribed';

    /**
     * @param HabitSubscriptionDataModel|null $subscription the caller's subscription to the habit asked for, if any
     * @param array<string, list<string>>     $violations
     *
     * @return array<string, list<string>>
     */
    public static function validate(?HabitSubscriptionDataModel $subscription, UserDataModel $owner, array $violations = []): array
    {
        if (
            null === $subscription
            || false === $subscription->isActive
            || false === $subscription->habit->isActive
            || $subscription->owner->id !== $owner->id
        ) {
            $violations['habitId'][] = self::HABIT_NOT_SUBSCRIBED;
        }

        return $violations;
    }
}
