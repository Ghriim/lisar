<?php

declare(strict_types=1);

namespace App\UseCase\Admin;

use App\Domain\DTO\Output\Habit\HabitAdminDataOutput;
use App\Domain\Factory\OutputFactory\HabitAdminOutputFactory;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use App\UseCase\UseCaseInterface;

/** The whole catalogue for the back-office, active and retired alike, newest first. */
final readonly class ListHabitsForAdminUseCase implements UseCaseInterface
{
    public function __construct(
        private HabitProviderGateway $habitProviderGateway,
        private HabitAdminOutputFactory $outputFactory,
    ) {
    }

    /**
     * @return list<HabitAdminDataOutput>
     */
    public function execute(?bool $isActive = null): array
    {
        return $this->outputFactory->buildMany($this->habitProviderGateway->findAllForAdminList($isActive));
    }
}
