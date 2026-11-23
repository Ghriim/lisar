<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Planning\TrainingPlanningEntryDataModel;
use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Planning\TrainingPlanningEntryDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\TrainingPlanningOutputFactory;
use App\Domain\Gateway\Persister\Planning\TrainingPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Planning\TrainingPlanningEntryProviderGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Validation\Validator\Planning\UpdateTrainingPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * A training entry replaced whole. Nothing of the past is stored, so nothing of it moves: a slot
 * kept stays kept — it is read from the workouts — and the dates to come follow the new rule.
 */
final readonly class UpdateTrainingPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateTrainingPlanningEntryValidator $validator,
        private TrainingPlanningEntryProviderGateway $entryProviderGateway,
        private RoutineProviderGateway $routineProviderGateway,
        private TrainingPlanningEntryPersisterGateway $entryPersisterGateway,
        private TrainingPlanningEntryDataModelFactory $entryFactory,
        private TrainingPlanningOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $entryId, SaveTrainingPlanningEntryDataInput $input): TrainingPlanningEntryDataOutput
    {
        $entry = $this->entryProviderGateway->findOneById($entryId);
        if (null === $entry) {
            throw new DataModelNotFoundException(TrainingPlanningEntryDataModel::class);
        }

        // The one it plans already, without a second read.
        $routine = $entry->routine->id === $input->routineId ? $entry->routine : $this->routineProviderGateway->findOneById($input->routineId);

        $this->validator->validate($currentUser, $entry, $input, $routine);
        if (null === $routine) {
            // Never reached: the validator refuses a routine that does not exist.
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $this->entryFactory->apply($entry, $routine, $input->schedule);
        $this->entryPersisterGateway->update($entry);

        return $this->outputFactory->buildOne($entry);
    }
}
