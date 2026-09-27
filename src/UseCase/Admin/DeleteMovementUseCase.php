<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MovementDataModel;
use App\Domain\Gateway\Persister\MovementPersisterGateway;
use App\Domain\Gateway\Provider\MovementProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a common movement. Nothing refers to one yet; the day workouts do, this is where the
 * in-use check goes — and until then, deactivating is the way to retire one that has been used.
 */
final readonly class DeleteMovementUseCase implements UseCaseInterface
{
    public function __construct(
        private MovementProviderGateway $movementProviderGateway,
        private MovementPersisterGateway $movementPersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): void
    {
        $movement = $this->movementProviderGateway->findOneCommonById($id);
        if (null === $movement) {
            throw new DataModelNotFoundException(MovementDataModel::class);
        }

        $this->movementPersisterGateway->delete($movement);
    }
}
