<?php

declare(strict_types=1);

namespace App\UseCase\Habit;

use App\Domain\DTO\DataModel\HabitDataModel;
use App\Domain\DTO\DataModel\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\UserDataModel;
use App\Domain\DTO\Output\Habit\HabitCatalogItemDataOutput;
use App\Domain\Factory\OutputFactory\HabitCatalogItemOutputFactory;
use App\Domain\Gateway\Persister\HabitSubscriptionPersisterGateway;
use App\Domain\Gateway\Provider\HabitProviderGateway;
use App\Domain\Gateway\Provider\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Dropping a habit. The subscription is deactivated, not deleted: the days already kept stay, so
 * resuming later picks the run back up rather than starting it over.
 */
final readonly class UnsubscribeHabitUseCase implements UseCaseInterface
{
    public function __construct(
        private UserProviderGateway $userProviderGateway,
        private HabitProviderGateway $habitProviderGateway,
        private HabitSubscriptionProviderGateway $habitSubscriptionProviderGateway,
        private HabitSubscriptionPersisterGateway $habitSubscriptionPersisterGateway,
        private HabitCatalogItemOutputFactory $outputFactory,
    ) {
    }

    /**
     * @throws DataModelNotFoundException
     */
    public function execute(int $ownerId, int $habitId): HabitCatalogItemDataOutput
    {
        $owner = $this->userProviderGateway->findOneById($ownerId);
        if (null === $owner) {
            throw new DataModelNotFoundException(UserDataModel::class);
        }

        $habit = $this->habitProviderGateway->findOneById($habitId);
        if (null === $habit) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $subscription = $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($owner, $habit);
        if (null === $subscription || false === $subscription->isActive) {
            throw new DataModelNotFoundException(HabitSubscriptionDataModel::class);
        }

        $subscription->isActive = false;

        $this->habitSubscriptionPersisterGateway->update($subscription);

        return $this->outputFactory->buildOne($habit, false);
    }
}
