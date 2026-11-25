<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\HabitPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Planning\HabitPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Planning\HabitPlanningEntryProviderGateway;
use App\Domain\Validation\Validator\Planning\DeleteHabitPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * A habit entry gone, its dates to come with it. The days the habit was kept stay. Its last entry
 * cannot go: a kept habit always has a planning.
 */
final readonly class DeleteHabitPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteHabitPlanningEntryValidator $validator,
        private HabitPlanningEntryProviderGateway $entryProviderGateway,
        private HabitPlanningEntryPersisterGateway $entryPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId): void
    {
        $entry = $this->entryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(HabitPlanningEntryDataModel::class);
        }

        $this->validator->validate($currentUser, $entry, $this->entryProviderGateway->findForSubscription($entry->subscription));

        $this->entryPersisterGateway->delete($entry);
    }
}
