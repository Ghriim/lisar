<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\DTO\Output\Training\MovementFamilyDataOutput;
use App\Domain\Factory\OutputFactory\Training\MovementFamilyOutputFactory;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Retiring a movement family. Every movement in it stops being offered, without its own flag
 * changing: reactivating the family gives them back exactly as they were.
 */
final readonly class DeactivateMovementFamilyUseCase implements UseCaseInterface
{
    public function __construct(
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MovementFamilyPersisterGateway $movementFamilyPersisterGateway,
        private MovementFamilyOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): MovementFamilyDataOutput
    {
        $movementFamily = $this->movementFamilyProviderGateway->findOneById($id);
        if (null === $movementFamily) {
            throw new DataModelNotFoundException(MovementFamilyDataModel::class);
        }

        $movementFamily->isActive = false;

        $this->movementFamilyPersisterGateway->update($movementFamily);

        return $this->outputFactory->buildOne($movementFamily);
    }
}
