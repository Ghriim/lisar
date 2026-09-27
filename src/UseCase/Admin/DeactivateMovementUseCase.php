<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\DTO\Output\Workout\MovementDataOutput;
use App\Domain\Factory\OutputFactory\MovementOutputFactory;
use App\Domain\Gateway\Persister\MovementPersisterGateway;
use App\Domain\Gateway\Provider\MovementProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Retiring a movement. It stops being offered; what was already logged with it keeps it. */
final readonly class DeactivateMovementUseCase implements UseCaseInterface
{
    public function __construct(
        private MovementProviderGateway $movementProviderGateway,
        private MovementPersisterGateway $movementPersisterGateway,
        private MovementOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): MovementDataOutput
    {
        $movement = $this->movementProviderGateway->findOneCommonById($id);
        if (null === $movement) {
            throw new DataModelNotFoundException(MovementDataModel::class);
        }

        $movement->isActive = false;

        $this->movementPersisterGateway->update($movement);

        return $this->outputFactory->buildOne($movement);
    }
}
