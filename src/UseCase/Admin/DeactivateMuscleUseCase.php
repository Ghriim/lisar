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
 * Retiring a muscle. It stops being offered to new movements; the movements already targeting it
 * keep it.
 */
final readonly class DeactivateMuscleUseCase implements UseCaseInterface
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

        $muscle->isActive = false;

        $this->musclePersisterGateway->update($muscle);

        return $this->outputFactory->buildOne($muscle);
    }
}
