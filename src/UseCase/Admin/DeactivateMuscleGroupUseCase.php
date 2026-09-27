<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleGroupDataModel;
use App\Domain\DTO\Output\Workout\MuscleGroupDataOutput;
use App\Domain\Factory\OutputFactory\MuscleGroupOutputFactory;
use App\Domain\Gateway\Persister\MuscleGroupPersisterGateway;
use App\Domain\Gateway\Provider\MuscleGroupProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Retiring a muscle group. Every muscle in it stops being offered to new movements, without its
 * own flag changing: reactivating the group gives them back exactly as they were.
 */
final readonly class DeactivateMuscleGroupUseCase implements UseCaseInterface
{
    public function __construct(
        private MuscleGroupProviderGateway $muscleGroupProviderGateway,
        private MuscleGroupPersisterGateway $muscleGroupPersisterGateway,
        private MuscleGroupOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): MuscleGroupDataOutput
    {
        $muscleGroup = $this->muscleGroupProviderGateway->findOneById($id);
        if (null === $muscleGroup) {
            throw new DataModelNotFoundException(MuscleGroupDataModel::class);
        }

        $muscleGroup->isActive = false;

        $this->muscleGroupPersisterGateway->update($muscleGroup);

        return $this->outputFactory->buildOne($muscleGroup);
    }
}
