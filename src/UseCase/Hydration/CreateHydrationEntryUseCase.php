<?php

declare(strict_types=1);

namespace App\UseCase\Hydration;

use App\Domain\DTO\DataModel\HydrationDayDataModel;
use App\Domain\DTO\DataModel\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Input\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Output\Hydration\HydrationDayDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\HydrationDayOutputFactory;
use App\Domain\Gateway\Persister\HydrationDayPersisterGateway;
use App\Domain\Gateway\Persister\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\HydrationDayProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Domain\Registry\Habit\HabitTrackerRegistry;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Hydration\CreateHydrationEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\Habit\SyncTrackerHabitsUseCase;
use App\UseCase\UseCaseInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Logging something drunk. Always on the day in progress: there is no way to write to another.
 *
 * It answers with the whole day rather than the entry alone — the widget shows a total and a
 * goal, and one round trip is enough to refresh both.
 */
final readonly class CreateHydrationEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateHydrationEntryValidator $validator,
        private UserProviderGateway $userProviderGateway,
        private HydrationDayProviderGateway $hydrationDayProviderGateway,
        private HydrationDayPersisterGateway $hydrationDayPersisterGateway,
        private HydrationEntryPersisterGateway $hydrationEntryPersisterGateway,
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
    public function execute(int $ownerId, CreateHydrationEntryDataInput $input): HydrationDayDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $this->validator->validate($input);

        $today = $this->clock->today();
        $day = $this->hydrationDayProviderGateway->findOneForOwnerAndDay($owner, $today);

        if (null === $day) {
            $day = new HydrationDayDataModel();
            $day->owner = $owner;
            $day->day = $today;
            // Frozen here, once: what the goal becomes later must not rewrite what this day was.
            $day->goalInMillilitres = $this->defaultGoalInMillilitres;

            $this->hydrationDayPersisterGateway->create($day);
        }

        $entry = new HydrationEntryDataModel();
        $entry->hydrationDay = $day;
        $entry->recordedAt = $this->clock->now();
        $entry->volumeInMillilitres = $input->volumeInMillilitres;

        $this->hydrationEntryPersisterGateway->create($entry);

        // Keep both sides in step: the total is read off this collection.
        $day->entries->add($entry);

        // The day's total changed: habits watching hydration are kept, or unkept, to match.
        $this->syncTrackerHabits->execute($ownerId, HabitTrackerRegistry::HYDRATION, $day->getTotalInMillilitres(), $today);

        return $this->outputFactory->buildOne($day);
    }
}
