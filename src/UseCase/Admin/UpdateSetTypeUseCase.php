<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\SetTypeDataModel;
use App\Domain\DTO\Input\Workout\UpdateSetTypeDataInput;
use App\Domain\DTO\Output\Workout\SetTypeDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\SetTypeOutputFactory;
use App\Domain\Gateway\Persister\SetTypePersisterGateway;
use App\Domain\Gateway\Provider\SetTypeProviderGateway;
use App\Domain\Validation\Validator\Workout\UpdateSetTypeValidator;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Renaming or recolouring a set type. The sets already carrying it follow: they point at the row,
 * not at a copy of its name.
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

        $this->setTypePersisterGateway->update($setType);

        return $this->outputFactory->buildOne($setType);
    }
}
