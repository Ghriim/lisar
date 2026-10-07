<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\Input\Training\CreateMovementFamilyDataInput;
use App\Domain\DTO\Output\Training\MovementFamilyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\MovementFamilyOutputFactory;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Domain\Validation\Validator\Training\CreateMovementFamilyValidator;
use App\UseCase\UseCaseInterface;

/** Adding a movement family, active at once and empty until movements are put in it. */
final readonly class CreateMovementFamilyUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateMovementFamilyValidator $validator,
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MovementFamilyPersisterGateway $movementFamilyPersisterGateway,
        private MovementFamilyOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateMovementFamilyDataInput $input): MovementFamilyDataOutput
    {
        $this->validator->validate($input, $this->movementFamilyProviderGateway->findOneByName($input->name));

        $movementFamily = new MovementFamilyDataModel();
        $movementFamily->name = $input->name;

        $this->movementFamilyPersisterGateway->create($movementFamily);

        return $this->outputFactory->buildOne($movementFamily);
    }
}
