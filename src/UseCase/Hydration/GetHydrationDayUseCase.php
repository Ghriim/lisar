<?php

declare(strict_types=1);

namespace App\UseCase\Hydration;

use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Hydration\HydrationDayDataOutput;
use App\Domain\Factory\OutputFactory\HydrationDayOutputFactory;
use App\Domain\Gateway\Provider\HydrationDayProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The day in progress. It writes nothing: a day exists from the moment something is logged on
 * it, and merely looking at the widget is not logging.
 */
final readonly class GetHydrationDayUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private HydrationDayProviderGateway $hydrationDayProviderGateway,
        private HydrationDayOutputFactory $outputFactory,
        private DayClock $clock,
        #[Autowire('%hydration_daily_goal%')]
        private int $defaultGoalInMillilitres,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId): HydrationDayDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $today = $this->clock->today();
        $day = $this->hydrationDayProviderGateway->findOneForOwnerAndDay($owner, $today);

        if (null === $day) {
            return $this->outputFactory->buildEmpty($today, $this->defaultGoalInMillilitres);
        }

        return $this->outputFactory->buildOne($day);
    }
}
