<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\ListPlanningDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Planning\TrainingPlanningOutputFactory;
use App\Domain\Gateway\Provider\Planning\TrainingPlanningEntryProviderGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Planning\TrainingPlanningCalendar;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Planning\ListTrainingPlanningValidator;
use App\UseCase\UseCaseInterface;
use DateTimeImmutable;

/**
 * The caller's training planning over some days: their entries, and the slots those give, each
 * kept, missed or to come.
 */
final readonly class ListTrainingPlanningUseCase implements UseCaseInterface
{
    public function __construct(
        private ListTrainingPlanningValidator $validator,
        private TrainingPlanningEntryProviderGateway $entryProviderGateway,
        private WorkoutProviderGateway $workoutProviderGateway,
        private TrainingPlanningCalendar $calendar,
        private TrainingPlanningOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, ListPlanningDataInput $input): TrainingPlanningDataOutput
    {
        $this->validator->validate($currentUser, $input);

        // The days as a person reads them, then the instants they cover, the last day whole.
        $from = $this->dayOf($input->from);
        $to = $this->dayOf($input->to);

        $entries = $this->entryProviderGateway->findAllForOwner($currentUser);
        $workouts = [] === $entries ? [] : $this->workoutProviderGateway->findStartedFromRoutineForOwnerBetween(
            $currentUser,
            $this->clock->asStoredInstant($from),
            $this->clock->asStoredInstant($to->modify('+1 day')),
        );

        return $this->outputFactory->buildMany($entries, $this->calendar->slotsBetween($entries, $workouts, $from, $to));
    }

    /** Midnight of that day, in the zone days are counted in. */
    private function dayOf(string $day): DateTimeImmutable
    {
        $zone = $this->clock->today()->getTimezone();

        return DateTimeImmutable::createFromFormat('!Y-m-d', $day, $zone) ?: $this->clock->today();
    }
}
