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
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Registry\Habits\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Constraint\Tracking\Hydration\EntryFromTodayConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\Habits\SyncTrackerHabitsUseCase;
use App\UseCase\UseCaseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Removing something logged by mistake. The day itself stays, goal and all: it is the record
 * that a day happened, and an empty day is a fact too.
 */
final readonly class DeleteHydrationEntryUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_hydration_entry_invalid';

    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private HydrationEntryProviderGateway $hydrationEntryProviderGateway,
        private HydrationEntryPersisterGateway $hydrationEntryPersisterGateway,
        private HydrationDayProviderGateway $hydrationDayProviderGateway,
        private HydrationDayOutputFactory $outputFactory,
        private DayClock $clock,
        private SyncTrackerHabitsUseCase $syncTrackerHabits,
        #[Autowire('%hydration_daily_goal%')]
        private int $defaultGoalInMillilitres,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $ownerId, int $entryId): HydrationDayDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $entry = $this->hydrationEntryProviderGateway->findOneByIdForOwner($entryId, $owner);
        if (null === $entry) {
            throw new DataModelNotFoundException(HydrationEntryDataModel::class);
        }

        $violations = EntryFromTodayConstraint::validate($this->clock->isCurrentDay($entry->hydrationDay->day));
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->hydrationEntryPersisterGateway->delete($entry);

        $today = $this->clock->today();
        $day = $this->hydrationDayProviderGateway->findOneForOwnerAndDay($owner, $today);

        // The day's total changed: habits watching hydration are kept, or unkept, to match.
        $total = null === $day ? 0 : $day->getTotalInMillilitres();
        $this->syncTrackerHabits->execute($ownerId, HabitTrackerRegistry::HYDRATION, $total, $today);

        return null === $day
            ? $this->outputFactory->buildEmpty($today, $this->defaultGoalInMillilitres)
            : $this->outputFactory->buildOne($day);
    }
}
