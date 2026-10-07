<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Constraint\Training\SetTypeNotDefaultConstraint;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Retiring a set type. It stops being offered to new sets; the sets already carrying it keep it.
 * The default is refused: a set logged without a type would have nothing to take.
 */
final readonly class DeactivateSetTypeUseCase implements UseCaseInterface
{
    public const string ERROR_CODE = 'deactivate_set_type_invalid';

    public function __construct(
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypePersisterGateway $setTypePersisterGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id): SetTypeDataOutput
    {
        $setType = $this->setTypeProviderGateway->findOneById($id);
        if (null === $setType) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        $violations = SetTypeNotDefaultConstraint::validate($setType);
        if (false === empty($violations)) {
            throw new ValidationException(self::ERROR_CODE, $violations);
        }

        $setType->isActive = false;

        $this->setTypePersisterGateway->update($setType);

        return $this->outputFactory->buildOne($setType);
    }
}
