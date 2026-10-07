<?php

declare(strict_types=1);

namespace App\UseCase\Training\Admin;

use App\Domain\DTO\DataModel\Training\MuscleDataModel;
use App\Domain\DTO\Output\Training\MuscleDataOutput;
use App\Domain\Factory\OutputFactory\Training\MuscleOutputFactory;
use App\Domain\Gateway\Persister\Training\MusclePersisterGateway;
use App\Domain\Gateway\Provider\Training\MuscleProviderGateway;
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
