<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\CreateMuscleDataInput;
use App\Domain\DTO\Output\Workout\MuscleDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\MuscleOutputFactory;
use App\Domain\Gateway\Persister\MusclePersisterGateway;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use App\Domain\Validation\Validator\Workout\CreateMuscleValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Adding a muscle to an active group. */
final readonly class CreateMuscleUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateMuscleValidator $validator,
        private MuscleProviderGateway $muscleProviderGateway,
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MusclePersisterGateway $musclePersisterGateway,
        private MuscleOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(CreateMuscleDataInput $input): MuscleDataOutput
    {
        $muscleGroup = $this->muscleGroupProviderGateway->findOneById($input->muscleGroupId);

        $this->validator->validate(
            $input,
            $this->muscleProviderGateway->findOneByName($input->name),
            $muscleGroup,
        );

        // Unreachable — the validator has just refused a missing group — but it types what follows.
        if (null === $muscleGroup) {
            throw new DataModelNotFoundException(MuscleGroupDataModel::class);
        }

        $muscle = new MuscleDataModel();

        $muscle->name = $input->name;
        $muscle->muscleGroup = $muscleGroup;

        $this->musclePersisterGateway->create($muscle);

        return $this->outputFactory->buildOne($muscle);
    }
}
