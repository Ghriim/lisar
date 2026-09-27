<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\UpdateMuscleGroupDataInput;
use App\Domain\DTO\Output\Workout\MuscleGroupDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\MuscleGroupOutputFactory;
use App\Domain\Gateway\Persister\MuscleGroupPersisterGateway;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use App\Domain\Validation\Validator\Workout\UpdateMuscleGroupValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Renaming a muscle group. */
final readonly class UpdateMuscleGroupUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateMuscleGroupValidator $validator,
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MuscleGroupPersisterGateway $muscleGroupPersisterGateway,
        private MuscleGroupOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateMuscleGroupDataInput $input): MuscleGroupDataOutput
    {
        $muscleGroup = $this->muscleGroupProviderGateway->findOneById($id);
        if (null === $muscleGroup) {
            throw new DataModelNotFoundException(MuscleGroupDataModel::class);
        }

        $this->validator->validate($input, $muscleGroup, $this->muscleGroupProviderGateway->findOneByName($input->name));
        $muscleGroup->name = $input->name;

        $this->muscleGroupPersisterGateway->update($muscleGroup);

        return $this->outputFactory->buildOne($muscleGroup);
    }
}
