<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\Output\Training\SetTypeDataOutput;
use App\Domain\Factory\OutputFactory\Training\SetTypeOutputFactory;
use App\Domain\Gateway\Provider\Training\SetTypeProviderGateway;
use App\UseCase\UseCaseInterface;

/**
 * The set types a set may take on now, by name.
 */
final readonly class ListWorkoutSetTypesUseCase implements UseCaseInterface
{
    public function __construct(
        private SetTypeProviderGateway $setTypeProviderGateway,
        private SetTypeOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<SetTypeDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany($this->setTypeProviderGateway->findAllActive());
    }
}
