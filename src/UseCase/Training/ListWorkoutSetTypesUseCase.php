<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\Domain\Validation\Validator\Training\ListWorkoutSetTypesValidator;
use App\UseCase\UseCaseInterface;

/**
 * The set types a set may take on now, by name.
 */
final readonly class ListWorkoutSetTypesUseCase implements UseCaseInterface
{
    public function __construct(
        private ListWorkoutSetTypesValidator $validator,
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<SetTypeDataOutput>
     */
    public function execute(UserDataModel $currentUser): array
    {
        $this->validator->validate($currentUser);

        return $this->outputFactory->buildMany($this->setTypeProviderGateway->findAllActive());
    }
}
