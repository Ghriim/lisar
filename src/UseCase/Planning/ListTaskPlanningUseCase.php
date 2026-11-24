<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Output\Planning\TaskPlanningDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Planning\TaskPlanningOutputFactory;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Planning\TaskPlanningCalendar;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Planning\ListTaskPlanningValidator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;

/**
 * The caller's task planning over some days: their entries, and the dates those give — open or
 * done once their task is made, to come before.
 */
final readonly class ListTaskPlanningUseCase implements UseCaseInterface
{
    public function __construct(
        private ListTaskPlanningValidator $validator,
        private TaskPlanningEntryProviderGateway $entryProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPlanningCalendar $calendar,
        private TaskPlanningOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListPlanningDataInput $input): TaskPlanningDataOutput
    {
        $this->validator->validate($currentUser, $input);

        $from = $this->dayOf($input->from);
        $to = $this->dayOf($input->to);

        $entries = $this->entryProviderGateway->findAllForOwner($currentUser);
        $tasks = [] === $entries ? [] : $this->taskProviderGateway->findPlannedForOwnerBetween($currentUser, $from, $to);

        return $this->outputFactory->buildMany($entries, $this->calendar->slotsBetween($entries, $tasks, $from, $to));
    }

    /** Midnight of that day, in the zone days are counted in. */
    private function dayOf(string $day): DateTimeImmutable
    {
        $zone = $this->clock->today()->getTimezone();

        return DateTimeImmutable::createFromFormat('!Y-m-d', $day, $zone) ?: $this->clock->today();
    }
}
