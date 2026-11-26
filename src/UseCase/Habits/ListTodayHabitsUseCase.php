<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Planning\HabitPlanningCalendar;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Habits\ListTodayHabitsValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

use function sprintf;

/**
 * The habits the day in progress asks for, as the dashboard lists them: the kept ones planned
 * today, or with no planning at all, each with its last seven days. A habit its planning skips
 * today is not in it. It writes nothing.
 */
final readonly class ListTodayHabitsUseCase implements UseCaseInterface
{
    public function __construct(
        private ListTodayHabitsValidator $validator,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitEntryProviderGateway $habitEntryProviderGateway,
        private HabitPlanningEntryProviderGateway $habitPlanningEntryProviderGateway,
        private HabitPlanningCalendar $planningCalendar,
        private HabitOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @return list<HabitDataOutput>
     *
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        $today = $this->clock->today();
        $since = $today->modify(sprintf('-%d days', HabitOutputFactory::WINDOW_IN_DAYS - 1));

        $plannedDays = $this->planningCalendar->plannedDaysBetween($this->habitPlanningEntryProviderGateway->findAllForOwner($currentUser), $since, $today);
        $subscriptions = $this->planningCalendar->plannedOn($this->habitSubscriptionProviderGateway->findActiveForOwner($currentUser), $plannedDays, $today);
        $entries = $this->habitEntryProviderGateway->findForOwnerSince($currentUser, $since);

        return $this->outputFactory->buildMany($subscriptions, $entries, $plannedDays, $today);
    }
}
