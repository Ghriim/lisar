<?php

declare(strict_types=1);

namespace App\UseCase\Hydration;

use App\Domain\DTO\DataModel\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\DTO\Output\Hydration\HydrationDayDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HydrationDayOutputFactory;
use App\Domain\Gateway\Persister\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\HydrationDayProviderGateway;
use App\Domain\Gateway\Provider\HydrationEntryProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Hydration\UpdateHydrationEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\Habit\SyncTrackerHabitsUseCase;
use App\UseCase\UseCaseInterface;

final readonly class UpdateHydrationEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateHydrationEntryValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private HydrationEntryProviderGateway $hydrationEntryProviderGateway,
        private HydrationEntryPersisterGateway $hydrationEntryPersisterGateway,
        private HydrationDayProviderGateway $hydrationDayProviderGateway,
        private HydrationDayOutputFactory $outputFactory,
        private DayClock $clock,
        private SyncTrackerHabitsUseCase $syncTrackerHabits,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $entryId, UpdateHydrationEntryDataInput $input): HydrationDayDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $entry = $this->hydrationEntryProviderGateway->findOneByIdForOwner($entryId, $owner);
        if (null === $entry) {
            throw new DataModelNotFoundException(HydrationEntryDataModel::class);
        }

        $this->validator->validate($input, $this->clock->isCurrentDay($entry->hydrationDay->day));

        $entry->volumeInMillilitres = $input->volumeInMillilitres;
        $this->hydrationEntryPersisterGateway->update($entry);

        // Re-read so the total counts every entry, not just the one in hand.
        $today = $this->clock->today();
        $day = $this->hydrationDayProviderGateway->findOneForOwnerAndDay($owner, $today) ?? $entry->hydrationDay;

        // The day's total changed: habits watching hydration are kept, or unkept, to match.
        $this->syncTrackerHabits->execute($ownerId, HabitTrackerRegistry::HYDRATION, $day->getTotalInMillilitres(), $today);

        return $this->outputFactory->buildOne($day);
    }
}
