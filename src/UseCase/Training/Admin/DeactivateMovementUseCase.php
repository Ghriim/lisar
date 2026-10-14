<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\MovementDataOutput;
use App\Domain\Factory\OutputFactory\Training\MovementOutputFactory;
use App\Domain\Gateway\Persister\Training\MovementPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Validator\Training\DeactivateMovementValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Retiring a movement. It stops being offered; what was already logged with it keeps it. */
final readonly class DeactivateMovementUseCase implements UseCaseInterface
{
    public function __construct(
        private DeactivateMovementValidator $validator,
        private MovementProviderGateway $movementProviderGateway,
        private MovementPersisterGateway $movementPersisterGateway,
        private MovementOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser, int $id): MovementDataOutput
    {
        $movement = $this->movementProviderGateway->findOneCommonById($id);
        if (null === $movement) {
            throw new DataModelNotFoundException(MovementDataModel::class);
        }

        $this->validator->validate($currentUser);

        $movement->isActive = false;

        $this->movementPersisterGateway->update($movement);

        return $this->outputFactory->buildOne($movement);
    }
}
