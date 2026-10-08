<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\SetTypeDataModel;
use App\Domain\DTO\Input\Training\CreateSetTypeDataInput;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Exception\ValidationException;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Persister\Training\SetTypePersisterGateway;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Validator\Training\CreateSetTypeValidator;
use App\UseCase\UseCaseInterface;

/**
 * Adding a set type, offered to new sets at once. Made the default, it takes the default from the
 * type that had it: there is exactly one.
 */
final readonly class CreateSetTypeUseCase implements UseCaseInterface
{
    public function __construct(
        private CreateSetTypeValidator $validator,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypePersisterGateway $setTypePersisterGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function execute(CreateSetTypeDataInput $input): SetTypeDataOutput
    {
        $this->validator->validate($input, $this->setTypeProviderGateway->findOneByName($input->name));

        $setType = new SetTypeDataModel();
        $setType->name = $input->name;
        $setType->colour = $input->colour;
        $setType->isDefaultType = $input->isDefaultType;
        $setType->countsForPersonalBests = $input->countsForPersonalBests;

        if (true === $setType->isDefaultType) {
            $this->takeTheDefault();
        }

        $this->setTypePersisterGateway->create($setType);

        return $this->outputFactory->buildOne($setType);
    }

    private function takeTheDefault(): void
    {
        $previous = $this->setTypeProviderGateway->findOneDefault();
        if (null === $previous) {
            return;
        }

        $previous->isDefaultType = false;
        $this->setTypePersisterGateway->update($previous);
    }
}
