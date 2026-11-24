<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Output\Planning\HabitPlanningDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Planning\HabitPlanningOutputFactory;
use App\Domain\Gateway\Provider\Habits\HabitEntryProviderGateway;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Planning\HabitPlanningCalendar;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Planning\ListHabitPlanningValidator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;

/**
 * The caller's habit planning over some days: their entries, the dates those give, each kept,
 * missed or to come, and the days a planned habit was kept unplanned. A habit without any entry
 * counts every day, and is not on the planning.
 */
final readonly class ListHabitPlanningUseCase implements UseCaseInterface
{
    public function __construct(
        private ListHabitPlanningValidator $validator,
        private HabitPlanningEntryProviderGateway $entryProviderGateway,
        private HabitEntryProviderGateway $habitEntryProviderGateway,
        private HabitPlanningCalendar $calendar,
        private HabitPlanningOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListPlanningDataInput $input): HabitPlanningDataOutput
    {
        $this->validator->validate($currentUser, $input);

        $from = $this->dayOf($input->from);
        $to = $this->dayOf($input->to);

        $entries = $this->entryProviderGateway->findAllForOwner($currentUser);
        $habitDays = [] === $entries ? [] : $this->habitEntryProviderGateway->findForOwnerBetween($currentUser, $from, $to);

        return $this->outputFactory->buildMany($entries, $this->calendar->slotsBetween($entries, $habitDays, $from, $to));
    }

    /** Midnight of that day, in the zone days are counted in. */
    private function dayOf(string $day): DateTimeImmutable
    {
        $zone = $this->clock->today()->getTimezone();

        return DateTimeImmutable::createFromFormat('!Y-m-d', $day, $zone) ?: $this->clock->today();
    }
}
