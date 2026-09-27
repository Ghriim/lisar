<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Input\Workout\CreateMuscleGroupDataInput;
use App\Domain\DTO\Output\Workout\MuscleGroupDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\MuscleGroupOutputFactory;
use App\Domain\Gateway\Persister\MuscleGroupPersisterGateway;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use App\Domain\Validation\Validator\Workout\CreateMuscleGroupValidator;
use App\UseCase\UseCaseInterface;

/** Adding a muscle group, active at once and empty until muscles are put in it. */
final readonly class CreateMuscleGroupUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateMuscleGroupValidator $validator,
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MuscleGroupPersisterGateway $muscleGroupPersisterGateway,
        private MuscleGroupOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateMuscleGroupDataInput $input): MuscleGroupDataOutput
    {
        $this->validator->validate($input, $this->muscleGroupProviderGateway->findOneByName($input->name));

        $muscleGroup = new MuscleGroupDataModel();
        $muscleGroup->name = $input->name;

        $this->muscleGroupPersisterGateway->create($muscleGroup);

        return $this->outputFactory->buildOne($muscleGroup);
    }
}
