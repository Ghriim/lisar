<?php

declare(strict_types=1);

namespace App\UseCase\Training;

use App\Domain\DTO\Output\Training\MovementDataOutput;
use App\Domain\Factory\OutputFactory\Training\MovementOutputFactory;
use App\Domain\Gateway\Provider\Training\MovementProviderGateway;
use App\UseCase\UseCaseInterface;

/**
 * The movements a workout may take on now, by name: active, in an active family. Not paginated:
 * the picker searches and filters them by family, muscle and equipment on its own.
 */
final readonly class ListWorkoutMovementsUseCase implements UseCaseInterface
{
    public function __construct(
        private MovementProviderGateway $movementProviderGateway,
        private MovementOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<MovementDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany($this->movementProviderGateway->findAllOffered());
    }
}
