<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\Output\Training\MuscleGroupDataOutput;
use App\Domain\Factory\OutputFactory\Training\MuscleGroupOutputFactory;
use App\Domain\Gateway\Provider\Training\MuscleGroupProviderGateway;
use App\UseCase\UseCaseInterface;

/** Every muscle group, active and retired alike, by name. Not paginated: reference data an administrator keeps in a few dozen rows. */
final readonly class ListMuscleGroupsForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MuscleGroupOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<MuscleGroupDataOutput>
     */
    public function execute(): array
    {
        return $this->outputFactory->buildMany($this->muscleGroupProviderGateway->findAllForAdminList());
    }
}
