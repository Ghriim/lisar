<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\Input\Training\Admin\ListMusclesForAdminDataInput;
use App\Domain\DTO\Output\Training\MuscleDataOutput;
use App\Domain\Factory\OutputFactory\Training\MuscleOutputFactory;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
use App\UseCase\UseCaseInterface;

/** Every muscle for the back-office, active and retired alike, by group then by name. Not paginated: reference data an administrator keeps in a few dozen rows. */
final readonly class ListMusclesForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private MuscleProviderGateway $muscleProviderGateway,
        private MuscleOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<MuscleDataOutput>
     */
    public function execute(ListMusclesForAdminDataInput $input = new ListMusclesForAdminDataInput()): array
    {
        return $this->outputFactory->buildMany($this->muscleProviderGateway->findAllForAdminList(
            $input->isActive, $input->muscleGroupId,
        ));
    }
}
