<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\Input\Workout\UpdateMovementFamilyDataInput;
use App\Domain\DTO\Output\Workout\MovementFamilyDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\MovementFamilyOutputFactory;
use App\Domain\Gateway\Persister\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\MovementFamilyProviderGateway;
use App\Domain\Validation\Validator\Workout\UpdateMovementFamilyValidator;
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
