<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleGroupDataModel;
use App\Domain\DTO\Input\Training\CreateMuscleGroupDataInput;
use App\Domain\DTO\Output\Training\MuscleGroupDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\MuscleGroupOutputFactory;
use App\Domain\Gateway\Persister\Training\MuscleGroupPersisterGateway;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\Domain\Validation\Validator\Training\CreateMuscleGroupValidator;
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
