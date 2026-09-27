<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MovementFamilyDataModel;
use App\Domain\DTO\Output\Workout\MovementFamilyDataOutput;
use App\Domain\Factory\OutputFactory\MovementFamilyOutputFactory;
use App\Domain\Gateway\Persister\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\MovementFamilyProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Offering a retired movement family — and the active movements in it — again.
 */
final readonly class ActivateMovementFamilyUseCase implements UseCaseInterface
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

        $movementFamily->isActive = true;

        $this->movementFamilyPersisterGateway->update($movementFamily);

        return $this->outputFactory->buildOne($movementFamily);
    }
}
