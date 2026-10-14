<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\Training\WorkoutDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Input\Training\UpdateWorkoutDataInput;
use App\Domain\DTO\Output\Training\WorkoutDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\WorkoutOutputFactory;
use App\Domain\Gateway\Persister\Training\WorkoutPersisterGateway;
use App\Domain\Gateway\Provider\Training\WorkoutProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateWorkoutValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Naming a workout, noting it, saying how it felt — during it or after. When it started and
 * finished is never rewritten.
 */
final readonly class UpdateWorkoutUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateWorkoutValidator $validator,
        private WorkoutProviderGateway $workoutProviderGateway,
        private WorkoutPersisterGateway $workoutPersisterGateway,
        private WorkoutOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(UserDataModel $currentUser, int $workoutId, UpdateWorkoutDataInput $input): WorkoutDataOutput
    {
        $workout = $this->workoutProviderGateway->findOneById($workoutId);
        if (null === $workout) {
            throw new DataModelNotFoundException(WorkoutDataModel::class);
        }

        $this->validator->validate($currentUser, $workout, $input);

        $workout->name = $input->getName();
        $workout->note = $input->getNote();
        $workout->feeling = $input->feeling;

        $this->workoutPersisterGateway->update($workout);

        return $this->outputFactory->buildOne($workout);
    }
}
