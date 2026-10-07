<?php

declare(strict_types=1);

namespace App\UseCase\Habits;

use App\Domain\DTO\DataModel\Habits\HabitDataModel;
use App\Domain\DTO\DataModel\Habits\HabitSubscriptionDataModel;
use App\Domain\DTO\DataModel\User\UserDataModel;
use App\Domain\DTO\Output\Habits\HabitCatalogItemDataOutput;
use App\Domain\Factory\OutputFactory\Habits\HabitCatalogItemOutputFactory;
use App\Domain\Gateway\Persister\Habits\HabitSubscriptionPersisterGateway;
use App\Domain\Gateway\Provider\Habits\HabitProviderGateway;
use App\Domain\Gateway\Provider\Habits\HabitSubscriptionProviderGateway;
use App\Domain\Gateway\Provider\User\UserProviderGateway;
use App\Infrastructure\Exception\DataModelNotFoundException;
use App\UseCase\UseCaseInterface;

/**
 * Taking a habit on. Resuming a dropped one reactivates the same subscription rather than making a
 * second — so its past is kept, not started over.
 */
final readonly class SubscribeHabitUseCase implements UseCaseInterface
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
        if (null === $habit || false === $habit->isActive) {
            throw new DataModelNotFoundException(HabitDataModel::class);
        }

        $subscription = $this->habitSubscriptionProviderGateway->findOneForOwnerAndHabit($owner, $habit);

        if (null === $subscription) {
            $subscription = new HabitSubscriptionDataModel();
            $subscription->owner = $owner;
            $subscription->habit = $habit;
            $subscription->isActive = true;

            $this->habitSubscriptionPersisterGateway->create($subscription);
        } else {
            $subscription->isActive = true;

            $this->habitSubscriptionPersisterGateway->update($subscription);
        }

        return $this->outputFactory->buildOne($habit, true);
    }
}
