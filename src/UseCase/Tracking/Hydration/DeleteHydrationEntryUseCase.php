<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Tracking\Hydration\HydrationDayOutputFactory;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Tracking\Hydration\DeleteHydrationEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removing something logged by mistake. The day itself stays, goal and all: it is the record
 * that a day happened, and an empty day is a fact too.
 */
final readonly class DeleteHydrationEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteHydrationEntryValidator $validator,
        private HydrationEntryProviderGateway $hydrationEntryProviderGateway,
        private HydrationEntryPersisterGateway $hydrationEntryPersisterGateway,
        private HydrationDayProviderGateway $hydrationDayProviderGateway,
        private HydrationDayOutputFactory $outputFactory,
        private DayClock $clock,
        #[Autowire('%hydration_daily_goal%')]
        private int $defaultGoalInMillilitres,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId): HydrationDayDataOutput
    {
        $entry = $this->hydrationEntryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(HydrationEntryDataModel::class);
        }

        $this->validator->validate($currentUser, $entry, $this->clock->isCurrentDay($entry->hydrationDay->day));

        $this->hydrationEntryPersisterGateway->delete($entry);

        $today = $this->clock->today();
        $day = $this->hydrationDayProviderGateway->findOneForOwnerAndDay($currentUser, $today);

        return null === $day
            ? $this->outputFactory->buildEmpty($today, $this->defaultGoalInMillilitres)
            : $this->outputFactory->buildOne($day);
    }
}
