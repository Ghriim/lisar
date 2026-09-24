<?php

declare(strict_types=1);

namespace App\UseCase\Step;

use App\Domain\DTO\DataModel\StepDayDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Step\SaveStepDayDataInput;
use App\Domain\DTO\Output\Step\StepDayDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\StepDayOutputFactory;
use App\Domain\Gateway\Persister\StepDayPersisterGateway;
use App\Domain\Gateway\Provider\StepDayProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use App\Domain\Registry\Step\StepSourceRegistry;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Step\SaveStepDayValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\Habit\SyncTrackerHabitsUseCase;
use App\UseCase\UseCaseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Recording today's step count. One use case, not a create and an update: the count is a running
 * total keyed on its day, so saving twice is correcting the first — and a single idempotent write
 * cannot be raced into two rows the way a create-then-update pair can.
 *
 * The day is never named by the caller. Whatever day a long-open page thinks it is, this writes
 * to the one in progress. The count is set to what was typed, never added to it: the same total
 * the person read off their watch, and the same total a mobile sync will one day send.
 */
final readonly class SaveStepDayUseCase implements UseCaseInterface
{
    public function __construct(
        private SaveStepDayValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private StepDayProviderGateway $stepDayProviderGateway,
        private StepDayPersisterGateway $stepDayPersisterGateway,
        private StepDayOutputFactory $outputFactory,
        private DayClock $clock,
        private SyncTrackerHabitsUseCase $syncTrackerHabits,
        #[Autowire('%step_daily_goal%')]
        private int $defaultGoalInSteps,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, SaveStepDayDataInput $input): StepDayDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate($input);

        $today = $this->clock->today();
        $stepDay = $this->stepDayProviderGateway->findOneForOwnerAndDay($owner, $today);
        $isFirstNoteOfTheDay = null === $stepDay;

        if (null === $stepDay) {
            $stepDay = new StepDayDataModel();
            $stepDay->owner = $owner;
            $stepDay->day = $today;
            // Frozen on creation, never rewritten: a later goal change must not rewrite a day
            // that is already counted.
            $stepDay->goalInSteps = $this->defaultGoalInSteps;
        }

        $stepDay->countInSteps = $input->countInSteps;
        // This use case is the manual one; the mobile sync to come will set its own source.
        $stepDay->source = StepSourceRegistry::MANUAL;

        if (true === $isFirstNoteOfTheDay) {
            $this->stepDayPersisterGateway->create($stepDay);
        } else {
            $this->stepDayPersisterGateway->update($stepDay);
        }

        // The figure changed: the habits that watch the step count are kept, or unkept, to match.
        $this->syncTrackerHabits->execute($ownerId, HabitTrackerRegistry::STEPS, $stepDay->countInSteps, $today);

        return $this->outputFactory->buildOne($stepDay);
    }
}
