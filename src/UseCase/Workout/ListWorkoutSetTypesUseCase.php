<?php

declare(strict_types=1);

namespace App\UseCase\Workout;

use App\Domain\DTO\Output\Workout\SetTypeDataOutput;
use App\Domain\Factory\OutputFactory\SetTypeOutputFactory;
use App\Domain\Gateway\Provider\SetTypeProviderGateway;
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
