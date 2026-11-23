<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Planning\TrainingPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Planning\TrainingPlanningEntryProviderGateway;
use App\Domain\Validation\Validator\Planning\DeleteTrainingPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** A training entry gone, its dates to come with it. The workouts that kept its slots stay. */
final readonly class DeleteTrainingPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private DeleteTrainingPlanningEntryValidator $validator,
        private TrainingPlanningEntryProviderGateway $entryProviderGateway,
        private TrainingPlanningEntryPersisterGateway $entryPersisterGateway,
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
            throw new DataModelNotFoundException(TrainingPlanningEntryDataModel::class);
        }

        $this->validator->validate($currentUser, $entry);

        $this->entryPersisterGateway->delete($entry);
    }
}
