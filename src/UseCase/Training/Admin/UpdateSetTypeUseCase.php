<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\UpdateSetTypeDataInput;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Validator\Training\UpdateSetTypeValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Renaming or recolouring a set type, or making it the default. The sets already carrying it
 * follow: they point at the row, not at a copy of its name. Becoming the default takes it from the
 * type that had it; the default is never unset, only given away.
 */
final readonly class UpdateSetTypeUseCase implements UseCaseInterface
{
    public function __construct(
        private UpdateSetTypeValidator $validator,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypePersisterGateway $setTypePersisterGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     * @throws ValidationException
     */
    public function execute(int $id, UpdateSetTypeDataInput $input): SetTypeDataOutput
    {
        $setType = $this->setTypeProviderGateway->findOneById($id);
        if (null === $setType) {
            throw new DataModelNotFoundException(SetTypeDataModel::class);
        }

        $this->validator->validate($input, $setType, $this->setTypeProviderGateway->findOneByName($input->name));
        $setType->name = $input->name;
        $setType->colour = $input->colour;

        $changed = [$setType];
        if (true === $input->isDefaultType && false === $setType->isDefaultType) {
            $previous = $this->setTypeProviderGateway->findOneDefault();
            if (null !== $previous) {
                $previous->isDefaultType = false;
                $changed[] = $previous;
            }

            $setType->isDefaultType = true;
        }

        $this->setTypePersisterGateway->updateMany($changed);

        return $this->outputFactory->buildOne($setType);
    }
}
