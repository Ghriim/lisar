<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\Input\Training\UpdateMovementFamilyDataInput;
use App\Domain\DTO\Output\Training\MovementFamilyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\MovementFamilyOutputFactory;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateMovementFamilyValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Renaming a movement family. */
final readonly class UpdateMovementFamilyUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateMovementFamilyValidator $validator,
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MovementFamilyPersisterGateway $movementFamilyPersisterGateway,
        private MovementFamilyOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateMovementFamilyDataInput $input): MovementFamilyDataOutput
    {
        $movementFamily = $this->movementFamilyProviderGateway->findOneById($id);
        if (null === $movementFamily) {
            throw new DataModelNotFoundException(MovementFamilyDataModel::class);
        }

        $this->validator->validate($input, $movementFamily, $this->movementFamilyProviderGateway->findOneByName($input->name));
        $movementFamily->name = $input->name;

        $this->movementFamilyPersisterGateway->update($movementFamily);

        return $this->outputFactory->buildOne($movementFamily);
    }
}
