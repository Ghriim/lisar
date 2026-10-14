<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Validator\Training\ActivateSetTypeValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Offering a retired set type to new sets again.
 */
final readonly class ActivateSetTypeUseCase implements UseCaseInterface
{
    public function __construct(
        private ActivateSetTypeValidator $validator,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypePersisterGateway $setTypePersisterGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(UserDataModel $currentUser, int $id): SetTypeDataOutput
    {
        $setType = $this->setTypeProviderGateway->findOneById($id);
        if (null === $setType) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        $this->validator->validate($currentUser);

        $setType->isActive = true;

        $this->setTypePersisterGateway->update($setType);

        return $this->outputFactory->buildOne($setType);
    }
}
