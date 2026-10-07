<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\Output\Training\MovementFamilyDataOutput;
use App\Domain\Factory\OutputFactory\Training\MovementFamilyOutputFactory;
use App\Domain\Gateway\Provider\Training\MovementFamilyProviderGateway;
use App\UseCase\UseCaseInterface;

/** Every movement family, active and retired alike, by name. Not paginated: reference data an administrator keeps in a few dozen rows. */
final readonly class ListMovementFamiliesForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private MovementFamilyProviderGateway $movementFamilyProviderGateway,
        private MovementFamilyOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<MovementFamilyDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany($this->movementFamilyProviderGateway->findAllForAdminList());
    }
}
