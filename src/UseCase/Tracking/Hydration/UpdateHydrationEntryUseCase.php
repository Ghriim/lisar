<?php

declare(strict_types=1);

namespace App\UseCase\Tracking\Hydration;

use App\Domain\DTO\DataModel\Tracking\HydrationEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Tracking\Hydration\UpdateHydrationEntryDataInput;
use App\Domain\DTO\Output\Tracking\Hydration\HydrationDayDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Tracking\Hydration\HydrationDayOutputFactory;
use App\Domain\Gateway\Persister\Tracking\Hydration\HydrationEntryPersisterGateway;
use App\Domain\Gateway\Provider\Tracking\Hydration\HydrationEntryProviderGateway;
use App\Domain\Tracking\DayClock;
use App\Domain\Validation\Validator\Tracking\Hydration\UpdateHydrationEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

final readonly class UpdateHydrationEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateHydrationEntryValidator $validator,
        private HydrationEntryProviderGateway $hydrationEntryProviderGateway,
        private HydrationEntryPersisterGateway $hydrationEntryPersisterGateway,
        private HydrationDayOutputFactory $outputFactory,
        private DayClock $clock,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId, UpdateHydrationEntryDataInput $input): HydrationDayDataOutput
    {
        $entry = $this->hydrationEntryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(HydrationEntryDataModel::class);
        }

        $this->validator->validate($currentUser, $entry, $input, $this->clock->isCurrentDay($entry->hydrationDay->day));

        $entry->volumeInMillilitres = $input->volumeInMillilitres;
        $this->hydrationEntryPersisterGateway->update($entry);

        // Today, the validator saw to it: the entry's own day, whose entries count every drink.
        return $this->outputFactory->buildOne($entry->hydrationDay);
    }
}
