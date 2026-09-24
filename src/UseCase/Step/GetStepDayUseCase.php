<?php

declare(strict_types=1);

namespace App\UseCase\Step;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Step\StepDayDataOutput;
use App\Domain\Factory\OutputFactory\StepDayOutputFactory;
use App\Domain\Gateway\Provider\StepDayProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Tracking\DayClock;
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
        private UserProviderGateway $userProviderGateway,
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
    public function execute(int $ownerId): StepDayDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $today = $this->clock->today();
        $stepDay = $this->stepDayProviderGateway->findOneForOwnerAndDay($owner, $today);

        if (null === $stepDay) {
            return $this->outputFactory->buildEmpty($today, $this->defaultGoalInSteps);
        }

        return $this->outputFactory->buildOne($stepDay);
    }
}
