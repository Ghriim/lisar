<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MovementFamilyDataModel;
use App\Domain\Exception\ValidationException;
use App\Domain\Gateway\Persister\Training\MovementFamilyPersisterGateway;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\Domain\Validation\Constraint\Training\MovementFamilyUnusedConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a movement family, once it is empty. A family still holding movements — anyone's,
 * active or not — is refused: every movement sits in a family, and the answer to "retire it
 * anyway" is deactivating it.
 */
final readonly class DeleteMovementFamilyUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'delete_movement_family_invalid';

    public function __construct(
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MovementFamilyPersisterGateway $movementFamilyPersisterGateway,
        private MovementProviderGateway $movementProviderGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): void
    {
        $movementFamily = $this->movementFamilyProviderGateway->findOneById($id);
        if (null === $movementFamily) {
            throw new DataModelNotFoundException(MovementFamilyDataModel::class);
        }

        $violations = MovementFamilyUnusedConstraint::validate(
            $this->movementProviderGateway->countForMovementFamily($movementFamily),
        );

        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $this->movementFamilyPersisterGateway->delete($movementFamily);
    }
}
