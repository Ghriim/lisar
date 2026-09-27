<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\UpdateMuscleDataInput;
use App\Domain\DTO\Output\Workout\MuscleDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\MuscleOutputFactory;
use App\Domain\Gateway\Persister\MusclePersisterGateway;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use App\Domain\Validation\Validator\Workout\UpdateMuscleValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Renaming a muscle, or moving it to another active group. */
final readonly class UpdateMuscleUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateMuscleValidator $validator,
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
    public function execute(int $id, UpdateMuscleDataInput $input): MuscleDataOutput
    {
        $muscle = $this->muscleProviderGateway->findOneById($id);
        if (null === $muscle) {
            throw new DataModelNotFoundException(MuscleDataModel::class);
        }

        $muscleGroup = $this->muscleGroupProviderGateway->findOneById($input->muscleGroupId);

        $this->validator->validate(
            $input,
            $muscle,
            $this->muscleProviderGateway->findOneByName($input->name),
            $muscleGroup,
        );

        // Unreachable — the validator has just refused a missing group — but it types what follows.
        if (null === $muscleGroup) {
            throw new DataModelNotFoundException(MuscleGroupDataModel::class);
        }

        $muscle->name = $input->name;
        $muscle->muscleGroup = $muscleGroup;

        $this->musclePersisterGateway->update($muscle);

        return $this->outputFactory->buildOne($muscle);
    }
}
