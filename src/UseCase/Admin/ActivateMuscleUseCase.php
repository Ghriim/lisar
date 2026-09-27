<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\DTO\Output\Workout\MuscleDataOutput;
use App\Domain\Factory\OutputFactory\MuscleOutputFactory;
use App\Domain\Gateway\Persister\MusclePersisterGateway;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Offering a retired muscle to new movements again — provided its group is active too.
 */
final readonly class ActivateMuscleUseCase implements UseCaseInterface
{
    public function __construct(
        private MuscleProviderGateway $muscleProviderGateway,
        private MusclePersisterGateway $musclePersisterGateway,
        private MuscleOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): MuscleDataOutput
    {
        $muscle = $this->muscleProviderGateway->findOneById($id);
        if (null === $muscle) {
            throw new DataModelNotFoundException(MuscleDataModel::class);
        }

        $muscle->isActive = true;

        $this->musclePersisterGateway->update($muscle);

        return $this->outputFactory->buildOne($muscle);
    }
}
