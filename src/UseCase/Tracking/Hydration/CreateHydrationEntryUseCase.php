<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationDayDataModel;
use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\CreateHydrationEntryDataInput;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Tracking\Hydration\HydrationDayOutputFactory;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationDayPersisterGateway;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationDayProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Tracking\Hydration\CreateHydrationEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
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

        // The persister adds it to its day, and the habits watching hydration follow the total.
        $this->hydrationEntryPersisterGateway->create($entry);

        return $this->outputFactory->buildOne($day);
    }
}
