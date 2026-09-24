<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\Output\Habit\HabitAdminDataOutput;
use App\Domain\Factory\OutputFactory\HabitAdminOutputFactory;
use App\Domain\Gateway\Persister\HabitPersisterGateway;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/** Putting a retired habit back in the catalogue, offered again with its history intact. */
final readonly class ActivateHabitUseCase implements UseCaseInterface
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

        $habit->isActive = true;

        $this->habitPersisterGateway->update($habit);

        return $this->outputFactory->buildOne($habit);
    }
}
