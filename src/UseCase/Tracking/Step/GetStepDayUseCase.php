<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Step;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Tracking\Step\StepDayDataOutput;
use App\Domain\Factory\OutputFactory\Tracking\Step\StepDayOutputFactory;
use App\Domain\Gateway\Provider\Tracking\Step\StepDayProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Tracking\Step\GetStepDayValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The count of the day in progress, or nothing yet.
 *
 * Deliberately today's, not "the last day walked": steps are counted against a daily goal, and
 * yesterday's total says nothing about today's progress. It writes nothing — a day exists from
 * the moment it is recorded, and looking at the widget is not recording one.
 */
final readonly class GetStepDayUseCase implements UseCaseInterface
{
    public function __construct(
        private GetStepDayValidator $validator,
        private StepDayProviderGateway $stepDayProviderGateway,
        private StepDayOutputFactory $outputFactory,
        private DayClock $clock,
        #[Autowire('%step_daily_goal%')]
        private int $defaultGoalInSteps,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser): StepDayDataOutput
    {
        $this->validator->validate($currentUser);

        $today = $this->clock->today();
        $stepDay = $this->stepDayProviderGateway->findOneForOwnerAndDay($currentUser, $today);

        if (null === $stepDay) {
            return $this->outputFactory->buildEmpty($today, $this->defaultGoalInSteps);
        }

        return $this->outputFactory->buildOne($stepDay);
    }
}
