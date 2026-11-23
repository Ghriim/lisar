<?php

declare(strict_types=1);

namespace App\UseCase\Planning;

use App\Domain\DTO\DataModel\Training\RoutineDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Planning\SaveTrainingPlanningEntryDataInput;
use App\Domain\DTO\Output\Planning\TrainingPlanningEntryDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\DataModelFactory\Planning\TrainingPlanningEntryDataModelFactory;
use App\Domain\Factory\OutputFactory\Planning\TrainingPlanningOutputFactory;
use App\Domain\Gateway\Persister\Planning\TrainingPlanningEntryPersisterGateway;
use App\Domain\Gateway\Provider\Training\RoutineProviderGateway;
use App\Domain\Validation\Validator\Planning\CreateTrainingPlanningEntryValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** One of the caller's routines planned on a rule of dates. Nothing is written into the workout history. */
final readonly class CreateTrainingPlanningEntryUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateTrainingPlanningEntryValidator $validator,
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
    public function execute(UserDataModel $currentUser, SaveTrainingPlanningEntryDataInput $input): TrainingPlanningEntryDataOutput
    {
        // Whoever it belongs to: someone else's routine is refused by the validator, as one not found.
        $routine = $this->routineProviderGateway->findOneById($input->routineId);

        $this->validator->validate($currentUser, $input, $routine);
        if (null === $routine) {
            // Never reached: the validator refuses a routine that does not exist.
            throw new DataModelNotFoundException(RoutineDataModel::class);
        }

        $entry = $this->entryFactory->build($currentUser, $routine, $input->schedule);
        $this->entryPersisterGateway->create($entry);

        return $this->outputFactory->buildOne($entry);
    }
}
