<?php

declare(strict_types=1);

namespace App\UseCase\Habits\Admin;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\Output\Habits\HabitAdminDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitAdminOutputFactory;
use App\Domain\Gateway\Persister\Habits\HabitPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Retiring a habit from the catalogue. It stops being offered and stops counting, but the row
 * stays — the days people kept it, and the experience they earned, outlive it.
 */
final readonly class DeactivateHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private HabitProviderGateway $habitProviderGateway,
        private HabitPersisterGateway $habitPersisterGateway,
        private HabitAdminOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $id): HabitAdminDataOutput
    {
        $habit = $this->habitProviderGateway->findOneById($id);
        if (null === $habit) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $habit->isActive = false;

        $this->habitPersisterGateway->update($habit);

        return $this->outputFactory->buildOne($habit);
    }
}
