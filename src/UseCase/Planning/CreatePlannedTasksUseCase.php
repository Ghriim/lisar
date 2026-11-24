<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\Input\Planning\CreatePlannedTasksDataInput;
use App\Domain\DTO\Output\Planning\PlannedTasksCreationDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Todo\TaskDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\PlannedTasksCreationOutputFactory;
use App\Domain\Gateway\Persister\Todo\TaskPersisterGateway;
use App\Domain\Gateway\Provider\Planning\TaskPlanningEntryProviderGateway;
use App\Domain\Gateway\Provider\Todo\TaskProviderGateway;
use App\Domain\Planning\TaskPlanningCalendar;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Planning\CreatePlannedTasksValidator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;

/**
 * The daily run: every recurring task entry falling on the day makes that day's task, whoever's
 * it is. An entry that already made it — created or changed that same day — makes nothing more,
 * so the run can be replayed. A single date's task was made with its entry, and is not this
 * run's. No caller: it runs on a schedule (CreatePlannedTasksCommand).
 */
final readonly class CreatePlannedTasksUseCase implements UseCaseInterface
{
    public function __construct(
        private CreatePlannedTasksValidator $validator,
        private TaskPlanningEntryProviderGateway $entryProviderGateway,
        private TaskProviderGateway $taskProviderGateway,
        private TaskPersisterGateway $taskPersisterGateway,
        private TaskPlanningCalendar $calendar,
        private TaskDataModelFactory $taskFactory,
        private PlannedTasksCreationOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreatePlannedTasksDataInput $input): PlannedTasksCreationDataOutput
    {
        $this->validator->validate($input);

        $day = null === $input->day
            ? $this->clock->today()
            : (DateTimeImmutable::createFromFormat('!Y-m-d', $input->day, $this->clock->today()->getTimezone()) ?: $this->clock->today());

        $alreadyMade = array_flip($this->taskProviderGateway->findPlanningEntryIdsPlannedOn($day));

        $created = 0;
        foreach ($this->entryProviderGateway->findRecurringSpanning($day) as $entry) {
            if (true === isset($alreadyMade[(int) $entry->id]) || false === $this->calendar->fallsOn($entry, $day)) {
                continue;
            }
            $this->taskPersisterGateway->create($this->taskFactory->buildPlanned($entry, $day));
            ++$created;
        }

        return $this->outputFactory->buildOne($day, $created);
    }
}
