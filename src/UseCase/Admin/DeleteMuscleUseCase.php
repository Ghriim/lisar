<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\MuscleDataModel;
use App\Domain\Gateway\Persister\MusclePersisterGateway;
use App\Domain\Gateway\Provider\MuscleProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Deleting a muscle. Nothing refers to one yet; the day movements do, this is where the in-use
 * check goes — and until then, deactivating is the way to retire one that has been used.
 */
final readonly class DeleteMuscleUseCase implements UseCaseInterface
{
    public function __construct(
        private MuscleProviderGateway $muscleProviderGateway,
        private MusclePersisterGateway $musclePersisterGateway,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): void
    {
        $muscle = $this->muscleProviderGateway->findOneById($id);
        if (null === $muscle) {
            throw new DataModelNotFoundException(MuscleDataModel::class);
        }

        $this->musclePersisterGateway->delete($muscle);
    }
}
